<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Duty;
use App\Models\ScheduleEntry;
use App\Models\User;
use App\Services\DutyRoster;
use App\Support\Labels;

/**
 * /admin/kujdestaria — daily duty (kujdestaria e ditës): which teachers keep
 * watch in the hall and on each floor, every school day, in each shift.
 */
final class DutyController extends AdminController
{
    /** GET /admin/kujdestaria?ndrrimi=1 */
    public function index(): Response
    {
        $shift = (int) $this->request->query('ndrrimi', '1');
        $shift = isset(Labels::SHIFTS[$shift]) ? $shift : 1;

        $values = [];
        foreach (Duty::forShift($this->yearId(), $shift) as $day => $posts) {
            foreach ($posts as $postId => $places) {
                foreach ($places as $place => $teacher) {
                    $values[DutyRoster::field($day, $postId, $place)] = (string) $teacher['id'];
                }
            }
        }

        return $this->grid($shift, $values, []);
    }

    /** POST /admin/kujdestaria/{shift} */
    public function update(int $shift): Response
    {
        if (!isset(Labels::SHIFTS[$shift])) {
            throw new HttpException(404);
        }

        $posts = Duty::posts();
        $input = [];
        foreach (Labels::SCHOOL_DAYS as $day) {
            foreach ($posts as $post) {
                for ($place = 1; $place <= (int) $post['places']; $place++) {
                    $field = DutyRoster::field($day, (int) $post['id'], $place);
                    $input[$field] = $this->request->string($field);
                }
            }
        }

        ['cells' => $cells, 'errors' => $errors] = DutyRoster::read($input, $posts);
        if ($errors !== []) {
            return $this->grid($shift, $input, $errors, 422);
        }

        DutyRoster::save($this->yearId(), $shift, $cells, (int) Auth::id());
        Session::flash('success', 'Kujdestaria e ditës e ndërrimit ' . Labels::shift($shift, true) . ' u ruajt.');

        return redirect('/admin/kujdestaria?ndrrimi=' . $shift);
    }

    /** POST /admin/kujdestaria/vendet/shto */
    public function storePost(): Response
    {
        [$name, $places, $errors] = $this->readPost(null);
        if ($errors !== []) {
            Session::flash('error', reset($errors));
            return redirect('/admin/kujdestaria');
        }

        DutyRoster::createPost($name, $places, (int) Auth::id());
        Session::flash('success', 'Vendi “' . $name . '” u shtua.');

        return redirect('/admin/kujdestaria');
    }

    /** POST /admin/kujdestaria/vendet/{id} */
    public function updatePost(int $id): Response
    {
        $post = Duty::findPost($id) ?? throw new HttpException(404);
        [$name, $places, $errors] = $this->readPost($id);
        if ($errors !== []) {
            Session::flash('error', reset($errors));
            return redirect('/admin/kujdestaria');
        }

        DutyRoster::updatePost($post, $name, $places, (int) Auth::id());
        Session::flash('success', 'Vendi “' . $name . '” u ruajt.');

        return redirect('/admin/kujdestaria');
    }

    /** POST /admin/kujdestaria/vendet/{id}/fshij — only a post nobody is on duty at */
    public function destroyPost(int $id): Response
    {
        $post = Duty::findPost($id) ?? throw new HttpException(404);
        $used = array_column(Duty::posts(), 'used', 'id')[$id] ?? 0;

        if ((int) $used > 0) {
            Session::flash('error', 'Në “' . $post['name'] . '” ka mësimdhënës kujdestarë. Hiqini ata së pari nga kujdestaria.');
            return redirect('/admin/kujdestaria');
        }

        DutyRoster::deletePost($post, (int) Auth::id());
        Session::flash('success', 'Vendi “' . $post['name'] . '” u hoq.');

        return redirect('/admin/kujdestaria');
    }

    /** @return array{0: string, 1: int, 2: array<string, string>} */
    private function readPost(?int $id): array
    {
        $values = ['name' => $this->request->string('name'), 'places' => $this->request->string('places')];
        $places = ctype_digit($values['places']) ? (int) $values['places'] : 0;

        $errors = (new Validator($values))
            ->required('name', 'Shkruani emrin e vendit, p.sh. “Kati i parë”.')
            ->maxLength('name', 60, 'Emri mund të ketë deri në 60 karaktere.')
            ->rule('name', !Duty::postNameTaken($values['name'], $id), 'Ky vend ekziston tashmë.')
            ->rule('places', $places >= 1 && $places <= 6, 'Numri i mësimdhënësve për vend duhet të jetë nga 1 deri në 6.')
            ->errors();

        return [$values['name'], $places, $errors];
    }

    private function grid(int $shift, array $values, array $errors, int $status = 200): Response
    {
        return $this->page('admin/duty', [
            'title'    => 'Kujdestaria e ditës',
            'styles'   => ['timetable'],
            'shift'    => $shift,
            'posts'    => Duty::posts(),
            'teachers' => User::teacherOptions(),
            'lessons'  => ScheduleEntry::lessonsPerDay($this->yearId(), $shift),
            'values'   => $values,
            'errors'   => $errors,
        ], 'duty', $status);
    }
}

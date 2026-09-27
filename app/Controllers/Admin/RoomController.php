<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Response;
use App\Core\Session;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Services\RoomForm;
use App\Services\Structure;
use App\Support\Format;

/**
 * /admin/sallat — rooms. Every class has its own room; other rooms (the gym,
 * laboratories) appear in the timetable only for the lessons held there.
 */
final class RoomController extends AdminController
{
    /** GET /admin/sallat */
    public function index(): Response
    {
        return $this->overview(['name' => '', 'capacity' => '', 'is_active' => true], []);
    }

    /** POST /admin/sallat/shto */
    public function store(): Response
    {
        $values = RoomForm::read($this->request);
        $errors = RoomForm::validate($values, null);

        if ($errors !== []) {
            return $this->overview($values, $errors, 422);
        }

        Structure::createRoom($values['name'], RoomForm::capacity($values), (int) Auth::id());
        Session::flash('success', 'Salla ' . $values['name'] . ' u shtua.');

        return redirect('/admin/sallat');
    }

    /** GET /admin/sallat/{id}/ndrysho */
    public function edit(int $id): Response
    {
        $room = $this->findOrFail($id);

        return $this->form($room, RoomForm::fromRoom($room), []);
    }

    /** POST /admin/sallat/{id}/ndrysho */
    public function update(int $id): Response
    {
        $room = $this->findOrFail($id);
        $values = RoomForm::read($this->request);
        $values['is_active'] = $this->request->input('is_active') === '1';
        $errors = RoomForm::validate($values, $id);

        if ($errors !== []) {
            return $this->form($room, $values, $errors, 422);
        }

        Structure::updateRoom($room, $values['name'], RoomForm::capacity($values), $values['is_active'], (int) Auth::id());
        Session::flash('success', 'Ndryshimet u ruajtën.');

        return redirect('/admin/sallat');
    }

    /** POST /admin/sallat/{id}/fshij — only a room no class or lesson uses */
    public function destroy(int $id): Response
    {
        $room = $this->findOrFail($id);

        if (Room::isUsed($id)) {
            Session::flash('error', 'Salla ' . $room['name'] . ' përdoret nga një klasë ose në orar. Mund ta bëni joaktive.');
            return redirect('/admin/sallat/' . $id . '/ndrysho');
        }

        Structure::deleteRoom($room, (int) Auth::id());
        Session::flash('success', 'Salla ' . $room['name'] . ' u fshi.');

        return redirect('/admin/sallat');
    }

    private function findOrFail(int $id): array
    {
        return Room::find($id) ?? throw new HttpException(404);
    }

    private function overview(array $values, array $errors, int $status = 200): Response
    {
        // Which classes of this year call each room home
        $homeOf = [];
        foreach (SchoolClass::overview($this->yearId()) as $class) {
            if ($class['home_room_id'] !== null) {
                $homeOf[(int) $class['home_room_id']][] = Format::classLabel((int) $class['grade_level'], (int) $class['section']);
            }
        }

        return $this->page('admin/rooms', [
            'title'  => 'Sallat',
            'rooms'  => Room::overview(),
            'homeOf' => $homeOf,
            'values' => $values,
            'errors' => $errors,
        ], 'rooms', $status);
    }

    private function form(array $room, array $values, array $errors, int $status = 200): Response
    {
        return $this->page('admin/room-edit', [
            'title'  => $room['name'],
            'room'   => $room,
            'values' => $values,
            'errors' => $errors,
            'used'   => Room::isUsed((int) $room['id']),
        ], 'rooms', $status);
    }
}

<?php
/**
 * One badge summing up whether a person can use the portal.
 *
 * @var array $person  needs status, has_credentials, last_login_at
 */
if ($person['status'] !== 'active') {
    echo '<span class="badge">Joaktiv</span>';
} elseif ((int) $person['has_credentials'] === 0) {
    echo '<span class="badge badge--plain">Pa fletë hyrjeje</span>';
} elseif ($person['last_login_at'] === null) {
    echo '<span class="badge badge--info">Fleta ende e papërdorur</span>';
} else {
    echo '<span class="badge badge--success">Aktiv</span>';
}

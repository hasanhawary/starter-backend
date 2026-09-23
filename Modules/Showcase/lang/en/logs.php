<?php

/*
|--------------------------------------------------------------------------
| Showcase Workflow Log Messages
|--------------------------------------------------------------------------
| Transition entries are stored delimiter-encoded and resolved through
| transWithParams() when they are read, so the status labels follow the
| reader's locale rather than the writer's.
*/

return [
    'system' => 'System',
    'status_set' => ':actor set the status to :to.',
    'status_changed' => ':actor moved the record from :from to :to.',
];

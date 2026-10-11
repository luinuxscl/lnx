<?php

namespace App\Support\RecordAccess;

/**
 * The abilities a record grant can carry.
 *
 * Kept deliberately small: `view` covers reading the record and `manage`
 * covers acting on it. A grant with no abilities stored means "both", so the
 * common case does not have to enumerate them.
 */
enum RecordAccessAbility: string
{
    case View = 'view';

    case Manage = 'manage';
}

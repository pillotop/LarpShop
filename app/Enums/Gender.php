<?php

namespace App\Enums;

enum Gender: string
{
    case Female = 'female';
    case Male = 'male';
    case NonBinary = 'non_binary';
    case Other = 'other';
    case PreferNotToSay = 'prefer_not_to_say';
}

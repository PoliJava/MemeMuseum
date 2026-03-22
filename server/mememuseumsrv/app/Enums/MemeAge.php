<?php

namespace App\Enums;

enum MemeAge: string
{
    case Ancient = 'Ancient (Pre-2004)';
    case Medieval = 'Medieval (2004-2008)';
    case Classic = 'Classic (2009-2013)';
    case Golden = 'Golden (2014 - 2016)';
    case Modern = 'Modern (2017 - 2020)';
    case Postmodern = 'Postmodern (2021 - Present)';
}
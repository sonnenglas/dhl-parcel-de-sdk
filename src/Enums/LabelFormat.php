<?php

declare(strict_types=1);

namespace Sonnenglas\DhlParcelDe\Enums;

enum LabelFormat: string
{

        // Dimensions: 210 mm x 297 mm
    case A4 = 'A4';

        // Dimensions: 103 mm x 199 mm
    case FORMAT_910_300_600 = '910-300-600';

        // Dimensions: 103 mm x 199 mm
    case FORMAT_910_300_610 = '910-300-610';

        // Dimensions: 103 mm x 199 mm
    case FORMAT_910_300_700 = '910-300-700';

        // Dimensions: 103 mm x 199 mm
    case FORMAT_910_300_700_OZ = '910-300-700-oz';

        // Dimensions: 103 mm x 199 mm
    case FORMAT_910_300_710 = '910-300-710';

        // Dimensions: 103 mm x 150 mm
    case FORMAT_910_300_300 = '910-300-300';

        // Dimensions: 103 mm x 150 mm
    case FORMAT_910_300_300_OZ = '910-300-300-oz';

        // Dimensions: 100 mm x 199 mm
    case FORMAT_910_300_400 = '910-300-400';

        // Dimensions: 100 mm x 199 mm
    case FORMAT_910_300_410 = '910-300-410';

        // Dimensions: 100 mm x 70 mm
    case FORMAT_100X70MM = '100x70mm';
}

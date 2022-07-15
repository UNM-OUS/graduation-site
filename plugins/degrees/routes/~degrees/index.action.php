<h1>Degree records</h1>
<?php

use DigraphCMS_Plugins\unmous\degrees\Degrees;
use DigraphCMS_Plugins\unmous\degrees\DegreeTable;

echo new DegreeTable(Degrees::select());

<?php
/*
 * Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY;without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

//include '../lib/includeMain.lib.php';
require "../../../../main.inc.php";

global $conf;
$langs->load('feuilledetemps@feuilledetemps');

header('Content-Type: text/javascript');

echo 'var HEURE_JOUR ='.null2zero($conf->global->HEURE_JOUR).";\n";
//echo 'var HEURE_SEMAINE ='.null2zero($conf->global->HEURE_SEMAINE).";\n";
echo 'var HEURE_SUP1 ='.null2zero($conf->global->HEURE_SUP1).";\n";
echo 'var ERR_HEURE_MAX_JOUR_DEPASSEMENT = "'.rtrim($langs->transnoentitiesnoconv('ERR_HEURE_MAX_JOUR_DEPASSEMENT'))."\";\n";
echo 'var ERR_HEURE_MAX_SEMAINE_DEPASSEMENT = "'.rtrim($langs->transnoentitiesnoconv('ERR_HEURE_MAX_SEMAINE_DEPASSEMENT'))."\";\n";
echo 'var WRN_HEURE_JOUR_DEPASSEMENT = "'.rtrim($langs->transnoentitiesnoconv('WRN_HEURE_JOUR_DEPASSEMENT'))."\";\n";
if($conf->global->FDT_USE_HS_CASE) {
    echo 'var WRN_35H_DEPASSEMENT = "'.rtrim($langs->transnoentitiesnoconv('WRN_35H_DEPASSEMENT_USE_HS_CASE'))."\";\n";
}
else {
    echo 'var WRN_35H_DEPASSEMENT = "'.rtrim($langs->transnoentitiesnoconv('WRN_35H_DEPASSEMENT'))."\";\n";
}
echo 'var WRN_PUBLIC_HOLIDAY = "'.rtrim($langs->transnoentitiesnoconv('WRN_PUBLIC_HOLIDAY'))."\";\n";
echo 'var USE_HS_CASE ='.null2zero($conf->global->FDT_USE_HS_CASE).";\n";
echo 'var FDT_COLUMN_MAX_TASK_DAY ='.null2zero($conf->global->FDT_COLUMN_MAX_TASK_DAY).";\n";


/** function to avoid null returned for an int
 *
 * @param int $value int to check
 * @return int int value or 0 if int is null
 */
function null2zero($value = '')
{
    return (empty($value))?0:$value;
}

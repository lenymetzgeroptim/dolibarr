<?php

function feuilledetemps_completesubstitutionarray(&$substitutionarray, $langs, $object) {
	global $conf, $db, $usertoprocess;

    if($usertoprocess->id > 0) {
        if($conf->donneesrh->enabled) {
            $extrafields = new ExtraFields($db);
            $extrafields->fetch_name_optionals_label('donneesrh_Positionetcoefficient');
            $userField = new UserField($db);
            $userField->id = $usertoprocess->id;
            $userField->table_element = 'donneesrh_Positionetcoefficient';
            $userField->fetch_optionals();

            $heure_semaine_hs = (!empty($userField->array_options['options_pasdroitrtt']) ? $conf->global->HEURE_SEMAINE_NO_RTT : $conf->global->HEURE_SEMAINE);
        }
        else {
            $heure_semaine_hs = (!empty($usertoprocess->array_options['options_pasdroitrtt']) ? $conf->global->HEURE_SEMAINE_NO_RTT : $conf->global->HEURE_SEMAINE);
        }

        if(empty($usertoprocess->array_options['options_heuremaxjour']) || !$conf->global->HEURE_SUP_SUPERIOR_HEURE_MAX_SEMAINE) {
            $heure_max_jour = ($conf->global->HEURE_MAX_JOUR > 0 ? $conf->global->HEURE_MAX_JOUR : 0);
        }
        else {
            $heure_max_jour = $usertoprocess->array_options['options_heuremaxjour'];
        }

        if(empty($usertoprocess->array_options['options_heuremaxsemaine']) || !$conf->global->HEURE_SUP_SUPERIOR_HEURE_MAX_SEMAINE) {
            $heure_max_semaine = ($conf->global->HEURE_MAX_SEMAINE > 0 ? $conf->global->HEURE_MAX_SEMAINE : 0);
        }
        else {
            $heure_max_semaine = $usertoprocess->array_options['options_heuremaxsemaine'];
        }

        $substitutionarray['__WEEK_HOUR_HS__'] = $heure_semaine_hs;
        $substitutionarray['__HS25_HOUR__'] = $conf->global->HEURE_SUP1;
        $substitutionarray['__WEEK_MAX_HOUR__'] = $heure_max_semaine;
        $substitutionarray['__DAY_MAX_HOUR__'] = $heure_max_jour;
    }
}
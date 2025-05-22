/* Copyright (C) 2021 Lény Metzger  <leny-07@hotmail.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

function afficherNomClient(nom) {
    if ($('#options_client_informe[value="1"]').is(':checked')) {
        if ($('tr[id*=nom_client]').length === 0) {
            let arg = "<tr id=\"nom_client\" class=\"valuefieldcreate holidaycustom_extras_nom_client trextrafields_collapse\" data-element=\"extrafield\" data-targetelement=\"holidaycustom\" data-targetid=\"\">";
            arg = arg + "<td class=\"wordbreak fieldrequired\">Nom du client</td>";
            arg = arg + "<td class=\"holidaycustom_extras_nom_client\">";
            arg = arg + "<input type=\"text\" class=\"flat minwidth100 maxwidthonsmartphone\" name=\"options_nom_client\" id=\"options_nom_client\" value=\"";
            if (nom !== null && nom !== '' && nom !== undefined) {
                arg = arg + nom;
            }
            arg = arg + "\"></td></tr>";
            $('tr.holidaycustom_extras_client_informe ').after(arg)
        }
    }
    else {
        $('tr[id*=nom_client]').remove();
    }
}
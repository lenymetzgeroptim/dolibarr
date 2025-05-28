<?php
/* Copyright (C) 2024 OT
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

/**
 * \file    core/modules/hooks/modOTProjectContact.class.php
 * \ingroup ot
 * \brief   Hook for project contact
 */

/**
 *  Class to manage hooks for project contact
 */
class ModOTProjectContact
{
    /**
     * @var DoliDB Database handler
     */
    public $db;

    /**
     * @var string Error code (or message)
     */
    public $error = '';

    /**
     * @var array Errors
     */
    public $errors = array();

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Add form fields to the form
     *
     * @param  array  $parameters Hook parameters
     * @param  object $object     Object
     * @param  string $action     Action
     * @return int                <0 if KO, >0 if OK
     */
    public function formObjectOptions($parameters, &$object, &$action)
    {
        global $conf, $user, $langs, $form;

        if (!isset($parameters['context']) || $parameters['context'] != 'projectcontactcard') {
            return 0;
        }

        $langs->load("ot@ot");

        // Add function field after the role field
        if ($action == 'addcontact') {
            require_once DOL_DOCUMENT_ROOT.'/custom/ot/class/contactfunction.class.php';
            $contactfunction = new ContactFunction($this->db);
            $functions = $contactfunction->fetchAll('', '', 0, 0, array('status'=>1));

            print '<tr>';
            print '<td class="fieldrequired">'.$langs->trans("Function").'</td>';
            print '<td>';
            print '<select name="function_id" class="flat">';
            print '<option value="">'.$langs->trans("SelectAFunction").'</option>';
            if (is_array($functions)) {
                foreach ($functions as $function) {
                    print '<option value="'.$function->id.'">'.$function->label.'</option>';
                }
            }
            print '</select>';
            print '</td>';
            print '</tr>';
        }

        return 1;
    }

    /**
     * Add action buttons to the form
     *
     * @param  array  $parameters Hook parameters
     * @param  object $object     Object
     * @param  string $action     Action
     * @return int                <0 if KO, >0 if OK
     */
    public function formAddObjectActions($parameters, &$object, &$action)
    {
        global $conf, $user, $langs, $form;

        if (!isset($parameters['context']) || $parameters['context'] != 'projectcontactcard') {
            return 0;
        }

        $langs->load("ot@ot");

        // Add function field
        if ($action == 'addcontact_confirm') {
            require_once DOL_DOCUMENT_ROOT.'/custom/ot/class/elementcontactfunction.class.php';
            $elementcontactfunction = new ElementContactFunction($this->db);
            $elementcontactfunction->element_id = $object->id;
            $elementcontactfunction->contact_id = GETPOST('contactid', 'int');
            $elementcontactfunction->function_id = GETPOST('function_id', 'int');
            $elementcontactfunction->date_creation = dol_now();

            $result = $elementcontactfunction->create($user);
            if ($result < 0) {
                $this->error = $elementcontactfunction->error;
                $this->errors = $elementcontactfunction->errors;
                return -1;
            }
        }

        return 1;
    }

    /**
     * Add column to the contact list
     *
     * @param  array  $parameters Hook parameters
     * @param  object $object     Object
     * @param  string $action     Action
     * @return int                <0 if KO, >0 if OK
     */
    public function formListOptions($parameters, &$object, &$action)
    {
        global $conf, $user, $langs, $form;

        if (!isset($parameters['context']) || $parameters['context'] != 'projectcontactcard') {
            return 0;
        }

        $langs->load("ot@ot");

        // Add function column to the list
        if ($action == 'list') {
            print '<th>'.$langs->trans("Function").'</th>';
        }

        return 1;
    }

    /**
     * Add function value to the contact list
     *
     * @param  array  $parameters Hook parameters
     * @param  object $object     Object
     * @param  string $action     Action
     * @return int                <0 if KO, >0 if OK
     */
    public function formListRow($parameters, &$object, &$action)
    {
        global $conf, $user, $langs, $form;

        if (!isset($parameters['context']) || $parameters['context'] != 'projectcontactcard') {
            return 0;
        }

        $langs->load("ot@ot");

        // Add function value to the list
        if ($action == 'list') {
            require_once DOL_DOCUMENT_ROOT.'/custom/ot/class/elementcontactfunction.class.php';
            $elementcontactfunction = new ElementContactFunction($this->db);
            $functions = $elementcontactfunction->fetchAll('', '', 0, 0, array('element_id'=>$object->id, 'contact_id'=>$parameters['contact_id']));
            
            print '<td>';
            if (is_array($functions) && count($functions) > 0) {
                $function = reset($functions);
                require_once DOL_DOCUMENT_ROOT.'/custom/ot/class/contactfunction.class.php';
                $contactfunction = new ContactFunction($this->db);
                $contactfunction->fetch($function->function_id);
                print $contactfunction->label;
            }
            print '</td>';
        }

        return 1;
    }
}
<?php
/* Copyright (c) 2013 Florian Henry  <florian.henry@open-concept.pro>
 * Copyright (C) 2015 Marcos García  <marcosgdf@gmail.com>
 * Copyright (C) 2018 Charlene Benke <charlie@patas-monkey.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/core/class/html.formprojet.class.php
 *      \ingroup    core
 *      \brief      Class file for html component project
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

/**
 *      Class to manage building of HTML components
 */
class ExtendedFormProjets extends FormProjets
{
	/**
	 *  Output a combo list with tasks qualified for a third party
	 *
	 * @param int $socid Id third party (-1=all, 0=only projects not linked to a third party, id=projects not linked or linked to third party id)
	 * @param int $selected Id task preselected
	 * @param string $htmlname Name of HTML select
	 * @param int $maxlength Maximum length of label
	 * @param int $option_only Return only html options lines without the select tag
	 * @param string $show_empty Add an empty line ('1' or string to show for empty line)
	 * @param int $discard_closed Discard closed projects (0=Keep, 1=hide completely, 2=Disable)
	 * @param int $forcefocus Force focus on field (works with javascript only)
	 * @param int $disabled Disabled
	 * @param string $morecss More css added to the select component
	 * @param string $projectsListId ''=Automatic filter on project allowed. List of id=Filter on project ids.
	 * @param string $showmore 'all' = Show project info, 'progress' = Show task progression, ''=Show nothing more
	 * @param User $usertofilter User object to use for filtering
	 * @param int 	$htmlid Html id to use instead of htmlname
	 * @param int 	$reload Execute sql to get task
	 * @return string           Return html content
	 */
	public function selectTasksCustom($socid = -1, $selected = '', $htmlname = 'taskid', $maxlength = 24, $option_only = 0, $show_empty = '1', $discard_closed = 0, $forcefocus = 0, $disabled = 0, $morecss = 'maxwidth500', $projectsListId = '', $showmore = 'all', $usertofilter = null, $htmlid = '', $reload = 1, &$task_load, $moreparam)
	{
		global $user, $conf, $langs;

		require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

		if (is_null($usertofilter)) {
			$usertofilter = $user;
		}

		if (empty($htmlid)) {
			$htmlid = $htmlname;
		}

		$out = '';

		$hideunselectables = false;
		if (getDolGlobalString('PROJECT_HIDE_UNSELECTABLES')) {
			$hideunselectables = true;
		}

		if (empty($projectsListId)) {
			if (!$usertofilter->hasRight('projet', 'all', 'lire')) {
				$projectstatic = new Project($this->db);
				$projectsListId = $projectstatic->getProjectsAuthorizedForUser($usertofilter, 0, 1);
			}
		}

		// Search all projects
		if($reload) {
			$sql = "SELECT t.rowid, t.ref as tref, t.label as tlabel, t.progress,";
			$sql .= " p.rowid as pid, p.ref, p.title, p.fk_soc, p.fk_statut, p.public, p.usage_task,";
			$sql .= " s.nom as name";
			$sql .= " FROM " . $this->db->prefix() . "projet as p";
			$sql .= " LEFT JOIN " . $this->db->prefix() . "societe as s ON s.rowid = p.fk_soc,";
			$sql .= " " . $this->db->prefix() . "projet_task as t";
			$sql .= " WHERE p.entity IN (" . getEntity('project') . ")";
			$sql .= " AND t.fk_projet = p.rowid";
			if ($projectsListId) {
				$sql .= " AND p.rowid IN (" . $this->db->sanitize($projectsListId) . ")";
			}
			if ($socid == 0) {
				$sql .= " AND (p.fk_soc=0 OR p.fk_soc IS NULL)";
			}
			if ($socid > 0) {
				$sql .= " AND (p.fk_soc=" . ((int) $socid) . " OR p.fk_soc IS NULL)";
			}
			$sql .= " ORDER BY p.ref, t.ref ASC";

			$resql = $this->db->query($sql);
		}

		if ($resql || !$reload) {
			// Use select2 selector
			if (empty($option_only) && !empty($conf->use_javascript_ajax)) {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$comboenhancement = ajax_combobox($htmlid, '', 0, $forcefocus);
				$out .= $comboenhancement;
				$morecss .= ' minwidth150imp';
			}

			if (empty($option_only)) {
				$out .= '<select class="valignmiddle flat' . ($morecss ? ' ' . $morecss : '') . '"' . ($disabled ? ' disabled="disabled"' : '') . ' id="' . $htmlid . '" name="' . $htmlname . '" '.$moreparam.'>';
			}
			if (!empty($show_empty)) {
				$out .= '<option value="0" class="optiongrey">';
				if (!is_numeric($show_empty)) {
					//if (!empty($conf->use_javascript_ajax)) $out .= '<span class="opacitymedium">aaa';
					$out .= $show_empty;
					//if (!empty($conf->use_javascript_ajax)) $out .= '</span>';
				} else {
					$out .= '&nbsp;';
				}
				$out .= '</option>';
			}

			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num && $reload) {
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					$task_load[$i] = $obj;

					// If we ask to filter on a company and user has no permission to see all companies and project is linked to another company, we hide project.
					if ($socid > 0 && (empty($obj->fk_soc) || $obj->fk_soc == $socid) && !$usertofilter->hasRight('societe', 'lire')) {
						// Do nothing
					} else {
						if ($discard_closed == 1 && $obj->fk_statut == Project::STATUS_CLOSED && $selected != $obj->rowid) {
							$i++;
							continue;
						}

						$labeltoshow = '';
						$titletoshow = '';

						$disabled = 0;
						if ($obj->fk_statut == Project::STATUS_DRAFT) {
							$disabled = 1;
						} elseif ($obj->fk_statut == Project::STATUS_CLOSED) {
							if ($discard_closed == 2) {
								$disabled = 1;
							}
						} elseif ($socid > 0 && (!empty($obj->fk_soc) && $obj->fk_soc != $socid)) {
							$disabled = 1;
						}

						if (preg_match('/all/', $showmore)) {
							$labeltoshow .= dol_trunc($obj->ref, 18); // Project ref
							//if ($obj->public) $labeltoshow.=' ('.$langs->trans("SharedProject").')';
							//else $labeltoshow.=' ('.$langs->trans("Private").')';
							$labeltoshow .= ' ' . dol_trunc($obj->title, $maxlength);
							$titletoshow = $labeltoshow;

							if ($obj->name) {
								$labeltoshow .= ' (' . $obj->name . ')';
								$titletoshow .= ' <span class="opacitymedium">(' . $obj->name . ')</span>';
							}

							$disabled = 0;
							if ($obj->fk_statut == Project::STATUS_DRAFT) {
								$disabled = 1;
								$labeltoshow .= ' - ' . $langs->trans("Draft");
								$titletoshow .= ' -  <span class="opacitymedium">' . $langs->trans("Draft") . '</span>';
							} elseif ($obj->fk_statut == Project::STATUS_CLOSED) {
								if ($discard_closed == 2) {
									$disabled = 1;
								}
								$labeltoshow .= ' - ' . $langs->trans("Closed");
								$titletoshow .= ' - <span class="opacitymedium">' . $langs->trans("Closed") . '</span>';
							} elseif ($socid > 0 && (!empty($obj->fk_soc) && $obj->fk_soc != $socid)) {
								$disabled = 1;
								$labeltoshow .= ' - ' . $langs->trans("LinkedToAnotherCompany");
								$titletoshow .= ' - <span class="opacitymedium">' . $langs->trans("LinkedToAnotherCompany") . '</span>';
							}
							$labeltoshow .= ' - ';
							$titletoshow .= ' - ';
						}

						if (preg_match('/projectstatut/', $showmore)) {
							$disabled = 0;
							if ($obj->fk_statut == Project::STATUS_DRAFT) {
								$disabled = 1;
								$labeltoshow .= $langs->trans("Draft").' - ';
								$titletoshow .= '<span class="opacitymedium">' . $langs->trans("Draft") . '</span>'.' - ';
							} elseif ($obj->fk_statut == Project::STATUS_CLOSED) {
								if ($discard_closed == 2) {
									$disabled = 1;
								}
								$labeltoshow .= $langs->trans("Closed").' - ';
								$titletoshow .= '<span class="opacitymedium">' . $langs->trans("Closed") . '</span>'.' - ';
							} elseif ($socid > 0 && (!empty($obj->fk_soc) && $obj->fk_soc != $socid)) {
								$disabled = 1;
								$labeltoshow .= $langs->trans("LinkedToAnotherCompany").' - ';
								$titletoshow .= '<span class="opacitymedium">' . $langs->trans("LinkedToAnotherCompany") . '</span>'.' - ';
							}
						}

						// Label for task
						$labeltoshow .= /*$obj->tref . ' ' .*/ dol_trunc($obj->tlabel, $maxlength);
						$titletoshow .= /*$obj->tref . ' ' .*/ dol_trunc($obj->tlabel, $maxlength);
						if ($obj->usage_task && preg_match('/progress/', $showmore)) {
							$labeltoshow .= ' <span class="opacitymedium">(' . $obj->progress . '%)</span>';
							$titletoshow .= ' <span class="opacitymedium">(' . $obj->progress . '%)</span>';
						}

						if (!empty($selected) && $selected == $obj->rowid) {
							$out .= '<option value="' . $obj->rowid . '" selected';
							$out .= ' data-html="' . dol_escape_htmltag($titletoshow) . '"';
							//if ($disabled) $out.=' disabled';						// with select2, field can't be preselected if disabled
							$out .= '>' . $labeltoshow . '</option>';
						} else {
							if ($hideunselectables && $disabled && ($selected != $obj->rowid)) {
								$resultat = '';
							} else {
								$resultat = '<option value="' . $obj->rowid . '"';
								if ($disabled) {
									$resultat .= ' disabled';
								}
								//if ($obj->public) $labeltoshow.=' ('.$langs->trans("Public").')';
								//else $labeltoshow.=' ('.$langs->trans("Private").')';
								$resultat .= ' data-html="' . dol_escape_htmltag($titletoshow) . '"';
								$resultat .= '>';
								$resultat .= $labeltoshow;
								$resultat .= '</option>';
							}
							$out .= $resultat;
						}
					}
					$i++;
				}
			}
			if (sizeof($task_load) > 0 && !$reload) {
				while ($i < sizeof($task_load)) {
					$obj = $task_load[$i];
					// If we ask to filter on a company and user has no permission to see all companies and project is linked to another company, we hide project.
					if ($socid > 0 && (empty($obj->fk_soc) || $obj->fk_soc == $socid) && !$usertofilter->hasRight('societe', 'lire')) {
						// Do nothing
					} else {
						if ($discard_closed == 1 && $obj->fk_statut == Project::STATUS_CLOSED && $selected != $obj->rowid) {
							$i++;
							continue;
						}

						$labeltoshow = '';
						$titletoshow = '';

						$disabled = 0;
						if ($obj->fk_statut == Project::STATUS_DRAFT) {
							$disabled = 1;
						} elseif ($obj->fk_statut == Project::STATUS_CLOSED) {
							if ($discard_closed == 2) {
								$disabled = 1;
							}
						} elseif ($socid > 0 && (!empty($obj->fk_soc) && $obj->fk_soc != $socid)) {
							$disabled = 1;
						}

						if (preg_match('/all/', $showmore)) {
							$labeltoshow .= dol_trunc($obj->ref, 18); // Project ref
							//if ($obj->public) $labeltoshow.=' ('.$langs->trans("SharedProject").')';
							//else $labeltoshow.=' ('.$langs->trans("Private").')';
							$labeltoshow .= ' ' . dol_trunc($obj->title, $maxlength);
							$titletoshow = $labeltoshow;

							if ($obj->name) {
								$labeltoshow .= ' (' . $obj->name . ')';
								$titletoshow .= ' <span class="opacitymedium">(' . $obj->name . ')</span>';
							}

							$disabled = 0;
							if ($obj->fk_statut == Project::STATUS_DRAFT) {
								$disabled = 1;
								$labeltoshow .= ' - ' . $langs->trans("Draft");
								$titletoshow .= ' -  <span class="opacitymedium">' . $langs->trans("Draft") . '</span>';
							} elseif ($obj->fk_statut == Project::STATUS_CLOSED) {
								if ($discard_closed == 2) {
									$disabled = 1;
								}
								$labeltoshow .= ' - ' . $langs->trans("Closed");
								$titletoshow .= ' - <span class="opacitymedium">' . $langs->trans("Closed") . '</span>';
							} elseif ($socid > 0 && (!empty($obj->fk_soc) && $obj->fk_soc != $socid)) {
								$disabled = 1;
								$labeltoshow .= ' - ' . $langs->trans("LinkedToAnotherCompany");
								$titletoshow .= ' - <span class="opacitymedium">' . $langs->trans("LinkedToAnotherCompany") . '</span>';
							}
							$labeltoshow .= ' - ';
							$titletoshow .= ' - ';
						}

						if (preg_match('/projectstatut/', $showmore)) {
							$disabled = 0;
							if ($obj->fk_statut == Project::STATUS_DRAFT) {
								$disabled = 1;
								$labeltoshow .= $langs->trans("Draft").' - ';
								$titletoshow .= '<span class="opacitymedium">' . $langs->trans("Draft") . '</span>'.' - ';
							} elseif ($obj->fk_statut == Project::STATUS_CLOSED) {
								if ($discard_closed == 2) {
									$disabled = 1;
								}
								$labeltoshow .= $langs->trans("Closed").' - ';
								$titletoshow .= '<span class="opacitymedium">' . $langs->trans("Closed") . '</span>'.' - ';
							} elseif ($socid > 0 && (!empty($obj->fk_soc) && $obj->fk_soc != $socid)) {
								$disabled = 1;
								$labeltoshow .= $langs->trans("LinkedToAnotherCompany").' - ';
								$titletoshow .= '<span class="opacitymedium">' . $langs->trans("LinkedToAnotherCompany") . '</span>'.' - ';
							}
						}

						// Label for task
						$labeltoshow .= /*$obj->tref . ' ' .*/ dol_trunc($obj->tlabel, $maxlength);
						$titletoshow .= /*$obj->tref . ' ' .*/ dol_trunc($obj->tlabel, $maxlength);
						if ($obj->usage_task && preg_match('/progress/', $showmore)) {
							$labeltoshow .= ' <span class="opacitymedium">(' . $obj->progress . '%)</span>';
							$titletoshow .= ' <span class="opacitymedium">(' . $obj->progress . '%)</span>';
						}

						if (!empty($selected) && $selected == $obj->rowid) {
							$out .= '<option value="' . $obj->rowid . '" selected';
							$out .= ' data-html="' . dol_escape_htmltag($titletoshow) . '"';
							//if ($disabled) $out.=' disabled';						// with select2, field can't be preselected if disabled
							$out .= '>' . $labeltoshow . '</option>';
						} else {
							if ($hideunselectables && $disabled && ($selected != $obj->rowid)) {
								$resultat = '';
							} else {
								$resultat = '<option value="' . $obj->rowid . '"';
								if ($disabled) {
									$resultat .= ' disabled';
								}
								//if ($obj->public) $labeltoshow.=' ('.$langs->trans("Public").')';
								//else $labeltoshow.=' ('.$langs->trans("Private").')';
								$resultat .= ' data-html="' . dol_escape_htmltag($titletoshow) . '"';
								$resultat .= '>';
								$resultat .= $labeltoshow;
								$resultat .= '</option>';
							}
							$out .= $resultat;
						}
					}
					$i++;
				}
			}

			if (empty($option_only)) {
				$out .= '</select>';
			}

			$this->nboftasks = $num;

			//print $out;

			$this->db->free($resql);
			return $out;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

}

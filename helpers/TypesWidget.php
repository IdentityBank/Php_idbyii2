<?php
# * ********************************************************************* *
# *                                                                       *
# *   Yii2 Models and Modules                                             *
# *   This file is part of idbyii2. This project may be found at:         *
# *   https://github.com/IdentityBank/Php_idbyii2.                        *
# *                                                                       *
# *   Copyright (C) 2020 by Identity Bank. All Rights Reserved.           *
# *   https://www.identitybank.eu - You belong to you                     *
# *                                                                       *
# *   This program is free software: you can redistribute it and/or       *
# *   modify it under the terms of the GNU Affero General Public          *
# *   License as published by the Free Software Foundation, either        *
# *   version 3 of the License, or (at your option) any later version.    *
# *                                                                       *
# *   This program is distributed in the hope that it will be useful,     *
# *   but WITHOUT ANY WARRANTY; without even the implied warranty of      *
# *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the        *
# *   GNU Affero General Public License for more details.                 *
# *                                                                       *
# *   You should have received a copy of the GNU Affero General Public    *
# *   License along with this program. If not, see                        *
# *   https://www.gnu.org/licenses/.                                      *
# *                                                                       *
# * ********************************************************************* *
################################################################################
# Namespace                                                                    #
################################################################################

namespace idbyii2\helpers;

################################################################################
# Use(s)                                                                       #
################################################################################

use yii\base\Widget;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class TypesWidget
 *
 * @package idbyii2\helpers
 */
class TypesWidget extends Widget
{

    private $header = null;
    private $datatypes = [];

    /**
     * TypesWidget constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->header = $config['header'];
        $this->datatypes = $config['datatypes'];
    }

    /**
     * @return void
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @return string
     */
    public function run()
    {
        $id = $this->getId();

        $html = "<div class=\"box box-default\" id=\"header_$id\">" . "\n";
        $html .= "<div class=\"box-header with-border\">" . "\n";
        $html .= "<h3 class=\"box-title\">" . $this->header . "</h3>" . "\n";
        $html .= "<div class=\"box-tools pull-right\">" . "\n";
        $html .= "<button type=\"button\" class=\"btn btn-box-tool\" data-widget=\"collapse\"><i class=\"fa fa-minus\"></i></button>"
            . "\n";
        $html .= "</div>" . "\n";
        $html .= "</div>" . "\n";
        $html .= "<!-- /.box-header -->" . "\n";

        $html .= "<div class=\"box-body\">" . "\n";
        $html .= "<div class=\"row\">" . "\n";
        $html .= "<div class=\"col-md-6\">" . "\n";
        $html .= "<div class=\"form-group\">" . "\n";
        $html .= "<select id=\"select_$id\">" . "\n";

        foreach ($this->datatypes as $type) {
            $html .= "<option value='" . $type["internal_name"] . "'>" . $type["display_name"] . "</option>" . "\n";
        }

        $html .= "</select>" . "\n";
        $html .= "</div>" . "\n";
        $html .= "<!-- /.form-group -->" . "\n";

        $html .= "<div class=\"form-group\">" . "\n";
        $html .= "<label for=\"internal_name\">Internal Name</label>" . "\n";
        $html .= "<input id=\"internal_name_$id\" type=\"text\" name=\"internal_name_$id\">" . "\n";
        $html .= "</div>" . "\n";
        $html .= "<!-- /.form-group -->" . "\n";

        $html .= "<div class=\"form-group\">" . "\n";
        $html .= "<label for=\"display_name\">Display Name</label>" . "\n";
        $html .= "<input id=\"display_name_$id\" type=\"text\" name=\"display_name_$id\">" . "\n";
        $html .= "</div>" . "\n";
        $html .= "<!-- /.form-group -->" . "\n";

        $html .= "<div class=\"form-group\">" . "\n";
        $html .= "<label for=\"required\">Required</label>" . "\n";
        $html .= "<input type=\"hidden\" value=\"off\" name=\"required_$id\">" . "\n";
        $html .= "<input id=\"required_$id\" type=\"checkbox\" name=\"required_$id\">" . "\n";
        $html .= "</div>" . "\n";
        $html .= "<!-- /.form-group -->" . "\n";

        $html .= "<div class=\"form-group\">" . "\n";
        $html .= "<label for=\"searchable\">Searchable</label>" . "\n";
        $html .= "<input type=\"hidden\" value=\"off\" name=\"searchable_$id\">" . "\n";
        $html .= "<input id=\"searchable_$id\" type=\"checkbox\" name=\"searchable_$id\">" . "\n";
        $html .= "</div>" . "\n";
        $html .= "<!-- /.form-group -->" . "\n";

        $html .= "<div class=\"form-group\">" . "\n";
        $html .= "<label for=\"sensitive\">Sensitive</label>" . "\n";
        $html .= "<input type=\"hidden\" value=\"off\" name=\"sensitive_$id\">" . "\n";
        $html .= "<input id=\"sensitive_$id\" type=\"checkbox\" name=\"sensitive_$id\">" . "\n";
        $html .= "</div>" . "\n";
        $html .= "<!-- /.form-group -->" . "\n";

        $html .= "<div class=\"form-group\">" . "\n";
        $html .= "<label for=\"used_for\">Used For</label>" . "\n";
        $html .= "<input id=\"used_for_$id\" type=\"text\" name=\"used_for_$id\">" . "\n";
        $html .= "</div>" . "\n";
        $html .= "<!-- /.form-group -->" . "\n";

        $html .= "<div class=\"form-group\">" . "\n";
        $html .= "<label for=\"retained_period\">Retained Period</label>" . "\n";
        $html .= "<input id=\"retained_period_$id\" type=\"text\" name=\"retained_period_$id\">" . "\n";
        $html .= "</div>" . "\n";
        $html .= "<!-- /.form-group -->" . "\n";

        $html .= "<div class=\"form-group\">" . "\n";
        $html .= "<label for=\"shared\">Shared</label>" . "\n";
        $html .= "<input id=\"shared_$id\" type=\"text\" name=\"shared_$id\">" . "\n";
        $html .= "</div>" . "\n";
        $html .= "<!-- /.form-group -->" . "\n";

        $html .= "</div>" . "\n";
        $html .= "<!-- /.box-body -->" . "\n";
        $html .= "</div>" . "\n";
        $html .= "</div>" . "\n";
        $html .= "</div>" . "\n";

        $html .= "<script>" . "\n";
        $html .= "var data_types = JSON.parse('" . json_encode($this->datatypes) . "');" . "\n";

        $html .= "$( \"#select_$id\" )" . "\n";
        $html .= ".change(function () {" . "\n";
        $html .= "var internal_name = \"\";" . "\n";
        $html .= "var display_name = \"\";" . "\n";
        $html .= "var retained_period = \"\";" . "\n";
        $html .= "var used_for = \"\";" . "\n";
        $html .= "var shared = \"\";" . "\n";
        $html .= "var required = null;" . "\n";
        $html .= "var sensitive = null;" . "\n";

        $html .= "$( \"#select_$id option:selected\" ).each(function() {" . "\n";
        $html .= "var obj = data_types[this.value];" . "\n";
        $html .= "internal_name += obj.internal_name;" . "\n";
        $html .= "display_name += obj.display_name;" . "\n";
        $html .= "retained_period += obj.retained_period;" . "\n";
        $html .= "used_for += obj.used_for;" . "\n";
        $html .= "shared += obj.shared;" . "\n";
        $html .= "required = obj.required;" . "\n";
        $html .= "sensitive = obj.sensitive;" . "\n";
        $html .= "});" . "\n";

        $html .= "$( \"#internal_name_$id\" ).val( internal_name );" . "\n";
        $html .= "$( \"#display_name_$id\" ).val( display_name );" . "\n";
        $html .= "$( \"#retained_period_$id\" ).val( retained_period );" . "\n";
        $html .= "$( \"#used_for_$id\" ).val( used_for );" . "\n";
        $html .= "$( \"#shared_$id\" ).val( shared );" . "\n";

        $html .= "if(required)" . "\n";
        $html .= "$(\"#required_$id\").prop(\"checked\", true);" . "\n";
        $html .= "else" . "\n";
        $html .= "$(\"#required_$id\").prop(\"checked\", false);" . "\n";

        $html .= "if(sensitive)" . "\n";
        $html .= "$(\"#sensitive_$id\").prop(\"checked\", true);" . "\n";
        $html .= "else" . "\n";
        $html .= "$(\"#sensitive_$id\").prop(\"checked\", false);" . "\n";

        $html .= "})" . "\n";
        $html .= ".change();" . "\n";
        $html .= "</script>" . "\n";

        return $html;
    }
}

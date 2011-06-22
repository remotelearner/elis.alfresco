<?php
/**
 * Manage files in an external DMS repository.
 *
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2009 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    elis
 * @subpackage File system
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2010 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

    require dirname(dirname(__FILE__)) . '/config.php';
    require $CFG->dirroot . '/mod/resource/lib.php';


    $choose = required_param('choose');

    require_login();

    print_header(get_string('localfilechoose', 'resource'));

    print_simple_box(get_string('localfileinfo', 'resource'), 'center');

    $chooseparts = explode('.', $choose);

    ?>
    <script language="javascript" type="text/javascript">
    <!--
    function set_value(txt) {
        if (txt.indexOf('/') > -1) {
            path = txt.substring(txt.indexOf('/'),txt.length);
        } else if (txt.indexOf('\\') > -1) {
            path = txt.substring(txt.indexOf('\\'),txt.length);
        } else {
            window.close();
        }
        opener.document.forms['<?php echo $chooseparts[0]."'].".$chooseparts[1] ?>.value = '<?php p(RESOURCE_LOCALPATH) ?>'+path;
        window.close();
    }
    -->
    </script>

    <br />
    <div align="center" class="form">
    <form name="myform">
    <input type="file" size="60" name="myfile"><br />
    <input type="button" value="<?php print_string('localfileselect','resource') ?>"
           onClick="return set_value(document.myform.myfile.value)">
    <input type="button" value="<?php print_string('cancel') ?>"
           onClick="window.close()">
    </form>
    </div>

    </body>
    </html>

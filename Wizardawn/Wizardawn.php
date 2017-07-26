<?php

namespace ssv_material_parser;

use Exception;
use Wizardawn\Models\Building;
use Wizardawn\Models\City;
use Wizardawn\Models\Map;
use Wizardawn\Models\NPC;

require_once 'Converter.php';

ini_set('max_input_vars', '100000');

?>
    <h1>Convert Wizardawn Files to the SSV Material theme</h1>
<?php
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    ?>
    <form action="#" method="post" enctype="multipart/form-data">
        <input type="hidden" name="save" value="upload">
        <input type="file" name="html_file"><br/>
        <select name="parse_output">
            <option value="mp_dd">D&D Objects</option>
            <option value="html">HTML</option>
        </select><br/>
        <input type="submit" value="Upload" name="submit">
        <input type="submit" value="Test" name="submit">
    </form>
    <?php
} else {
    $nextPage = '';
    switch ($_POST['save']) {
        case 'upload':
            $nextPage = 'npcs';
            if ($_POST['submit'] == 'Test') {
                if (isset($_SESSION['city'])) {
                    $city = $_SESSION['city'];
                    break;
                }
                $fileContent = file_get_html(Parser::URL . 'test/001.html');
            } else {
                if (!function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }
                $uploadedFile    = $_FILES['html_file'];
                $uploadOverrides = array('test_form' => false);
                $movedFile       = wp_handle_upload($uploadedFile, $uploadOverrides);
                if (!$movedFile || isset($movedFile['error']) || $movedFile['type'] != 'text/html') {
                    echo $movedFile['error'];
                    return;
                }
                $fileContent = file_get_html($movedFile['file']);
            }
            $city             = Converter::Convert($fileContent);
            $_SESSION['city'] = $city;
            if ($_POST['parse_output'] == 'html') {
                ?><textarea><?= $city->getHTML() ?></textarea><?php
            }
            break;
        case 'npcs':
            if (isset($_POST['next'])) {
                $nextPage = 'buildings';
                break;
            }
            if (isset($_POST['save_single'])) {
                $nextPage = 'npcs';
                $id = $_POST['save_single'];
                NPC::getFromPOST($id, true)->toWordPress();
            } else {
                $nextPage = 'buildings';
                foreach ($_POST['npc___save'] as $id) {
                    NPC::getFromPOST($id)->toWordPress();
                }
            }
            break;
        case 'buildings':
            if (isset($_POST['next'])) {
                $nextPage = 'city';
                break;
            }
            if (isset($_POST['previous'])) {
                $nextPage = 'npcs';
                break;
            }
            if (isset($_POST['save_single'])) {
                $nextPage = 'buildings';
                $id = $_POST['save_single'];
                Building::getFromPOST($id, true)->toWordPress();
            } else {
                $nextPage = 'city';
                foreach ($_POST['building___save'] as $id) {
                    Building::getFromPOST($id)->toWordPress();
                }
            }
            break;
        case 'city':
            if (isset($_POST['previous'])) {
                $nextPage = 'buildings';
                break;
            }
            $nextPage = 'done';
            /** @var City $city */
            $city = $_SESSION['city'];
            if ($_POST['saveCity'] == 'false') {
                break;
            }
            if ($_POST['saveMap'] == 'true') {
                $city->getMap()->updateFromPOST();
            }
            $city->toWordPress();
            mp_var_export($city);
            break;
    }

    switch ($nextPage) {
        case 'npcs':
            /** @var City $city */
            $city = $_SESSION['city'];
            ?>
            <form action="#" method="POST">
                <div style="padding-top: 10px;">
                    <input type="submit" name="next" class="button button-primary button-large" value="Buildings >">
                </div>
                <br/>
                <input type="hidden" name="save" value="npcs">
                <?php
                foreach ($city->getBuildings() as $key => $building) {
                    if ($building instanceof Building) {
                        foreach ($building->getNPCs() as $npc) {
                            if ($npc instanceof NPC) {
                                echo $npc->getHTML();
                            }
                        }
                    }
                }
                echo get_submit_button('Save NPCs');
                ?>
            </form>
            <?php
            break;
        case 'buildings':
            /** @var City $city */
            $city = $_SESSION['city'];
            ?>
            <form action="#" method="POST">
                <div style="padding-top: 10px;">
                    <input type="submit" name="previous" id="submit" class="button button-primary button-large" value="< NPC's">
                    <input type="submit" name="next" id="submit" class="button button-primary button-large" value="City >">
                </div>
                <br/>
                <input type="hidden" name="save" value="buildings">
                <?php
                foreach ($city->getBuildings() as $key => $building) {
                    if ($building instanceof Building) {
                        echo $building->getHTML();
                    }
                }
                echo get_submit_button('Save buildings');
                ?>
            </form>
            <?php
            break;
        case 'city':
            /** @var City $city */
            $city = $_SESSION['city'];
            ?>
            <form action="#" method="POST">
                <div style="padding-top: 10px;">
                    <input type="submit" name="previous" id="submit" class="button button-primary button-large" value="< Buildings">
                </div>
                <br/>
                <input type="hidden" name="save" value="city">
                <?php
                echo $city->getHTML();
                echo get_submit_button('Save city');
                ?>
            </form>
            <?php
            break;
    }
}

<?php

include '/opt/stateless/nginx/www/includes/config_env_puller.php';
include '/opt/stateless/nginx/www/includes/phvalheim-frontend-config.php';
include '../includes/db_sets.php';
include '../includes/db_gets.php';


function populateEnabledModList($pdo,$world,$getAllModsLatestVersion) {
        foreach ($getAllModsLatestVersion as $row) {
		$modUUID = $row['moduuid'];
		$modName = $row['name'];
		$modName = substr($modName,0,64);
		$modNameLen = strlen($modName);

		if ($modNameLen == 64) {
			$modName = $modName . "...";
		}

		$modOwner = $row['owner'];
		$modURL = $row['url'];

		$modLastUpdated = $row['version_date_created'];

		$modVersion = $row['version'];
		$modVersion = str_replace("\"","",$modVersion);

		$modSelectedCheck = modSelectedCheck($pdo,$world,$modUUID);

		if ($modSelectedCheck) {
			print "<tr>\n";
			print "  <td style='width:1px;'><input name='thunderstore_mods[]' value='" . $modUUID . "' type='checkbox' checked></input></td>\n";
			print "  <td style='padding-right:15px;'><a target='_blank' href='$modURL'>$modName</a></td>\n";
			print "  <td>$modOwner</td>\n";
			print "  <td>$modLastUpdated</td>\n";
			print "  <td>$modVersion</td>\n";
			print "\n";
		}
        }
}


function populateDepModList($pdo,$world,$getAllModsLatestVersion) {
        foreach ($getAllModsLatestVersion as $row) {
                $modUUID = $row['moduuid'];
                $modName = $row['name'];
                $modName = substr($modName,0,64);
                $modNameLen = strlen($modName);

                if ($modNameLen == 64) {
                        $modName = $modName . "...";
                }

                $modOwner = $row['owner'];
                $modURL = $row['url'];

                $modLastUpdated = $row['version_date_created'];

                $modVersion = $row['version'];
                $modVersion = str_replace("\"","",$modVersion);

                #$modExistCheck = modExistCheck($pdo,$world,$modUUID);
                $modIsDep = modIsDep($pdo,$world,$modUUID);

                if ($modIsDep) {
                        print "<tr>\n";
                        print "  <td style='width:1px;'><input name='thunderstore_mods[]' value='" . $modUUID . "' type='checkbox' checked disabled></input></td>\n";
                        print "  <td style='padding-right:15px;'><a target='_blank' href='$modURL'>$modName<label class='alt-color' style='font-size:11px;font-style:italic;'> added as dependency</label></a></td>\n";
                        print "  <td>$modOwner</td>\n";
                        print "  <td>$modLastUpdated</td>\n";
                        print "  <td>$modVersion</td>\n";
			print "\n";
                }
        }
}


function populateDisabledModList($pdo,$world,$getAllModsLatestVersion) {
        foreach ($getAllModsLatestVersion as $row) {
                $modUUID = $row['moduuid'];
                $modName = $row['name'];
		$modName = substr($modName,0,64);
                $modNameLen = strlen($modName);

                if ($modNameLen == 64) {
                        $modName = $modName . "...";
                }

                $modOwner = $row['owner'];
		$modURL = $row['url'];

		$modLastUpdated = $row['version_date_created'];

                $modVersion = $row['version'];
                $modVersion = str_replace("\"","",$modVersion);
		$modSelectedCheck = modSelectedCheck($pdo,$world,$modUUID);
		$modIsDep = modIsDep($pdo,$world,$modUUID);

                if (!$modSelectedCheck && !$modIsDep) {
                        print "<tr>\n";
                        print "  <td style='width:1px;'><input name='thunderstore_mods[]' value='" . $modUUID . "' type='checkbox'></input></td>\n";
                        print "  <td style='padding-right:15px;'><a target='_blank' href='$modURL'>$modName</a></td>\n";
                        print "  <td>$modOwner</td>\n";
			print "  <td>$modLastUpdated</td>\n";
                        print "  <td>$modVersion</td>\n";
			print "\n";
                }
	}
}


if (!empty($_GET['world'])) {
	$world = $_GET['world'];
}

if(isset($_POST['submit'])) {
	$world = $_POST['world'];

	# remove all mods from this world, clean slate
	deleteAllWorldMods($pdo,$world);

	# update database with new selected mod list
	$thunderstore_mods = $_POST['thunderstore_mods'];

        foreach ($thunderstore_mods as $mod) {
                addModToWorld($pdo,$world,$mod);
        }

	# set database to "update" after editing world
	updateWorld($pdo,$world);
	
	# go back to admin home after save
	header('Location: index.php');

}


$getAllModsLatestVersion = getAllModsLatestVersion($pdo,$world);

?>

<!DOCTYPE HTML>
<html>
	<head>
                <link rel="stylesheet" type="text/css" href="/css/jquery.dataTables.css?refreshcss=<?php echo rand(100, 1000)?>">
                <link rel="stylesheet" type="text/css" href="/css/phvalheimStyles.css?refreshcss=<?php echo rand(100, 1000)?>">
		<script type="text/javascript" charset="utf8" src="/js/jquery-3.6.0.js"></script>
		<script type="text/javascript" charset="utf8" src="/js/jquery.dataTables.js"></script>
		<link rel="stylesheet" type="text/css" href="/css/multicheckbox.css?refreshcss=<?php echo rand(100, 1000)?>">
                <script>
			// begin document load
                        $(document).ready( function () {
				// begin datatables
                                $('#modtable').DataTable({

                                        "rowCallback": function( row, data, index ) {
                                            if(index%2 == 0){
                                                $(row).removeClass('myodd myeven');
                                                $(row).addClass('myodd');
                                            }else{
                                                $(row).removeClass('myodd myeven');
                                                $(row).addClass('myeven');
                                            }
                                          },

                                        lengthMenu: [
                                            [20, 50, 75, -1],
                                            [20, 50, 75, 'All'],
                                        ],

                                        columnDefs: [
                                         { orderable: false, targets: [ 0, 1, 2, 3, 4 ] },
                                        ],
                                }); // end data tables

				// remove loading spinner after php populates table
                                document.getElementById("spinner").style.display = "none";

                        }); // end document load


			// execute this when the form is submitted
                        function onFormSubmit() {
				// clears search filter and changes list range to all items. This is needed for POST
				$('#modtable').DataTable().page.len('-1').draw();
				$('#modtable').DataTable().search( '' ).draw();
                        }


			// execute this when the submit button is clicked
			function onSubmitClick() {
				// disable scroll bar
				document.body.classList.add("noscroll");
				// display loading spinner
				document.getElementById("spinner").style.display = "block";
			}

			// only allow the form to be submitted once per page load
			var form_enabled = true;
			$().ready(function(){
			       $('#edit_world').on('submit', function(){
			               if (form_enabled) {
			                       form_enabled = false;
			                       return true;
			               }
	
			               return false;
			        });
			});

                </script>

	</head>

        <div id="spinner" class="loading style-2 overlay" style="display:none;"><div class="loading-wheel"></div></div>
	<body>
	        <!--<div class="overlay" id="overlay" name="overlay" style="display:none;"></div>-->
		<form id="edit_world" name="edit_world" method="post" action="edit_world.php" onSubmit="onFormSubmit()">
		      <div style="padding-top:10px;" class="">
                        <table class="outline" style="width:auto;margin-left:auto;margin-right:auto;vertical-align:middle;border-collapse:collapse;" border=0>
                                <th class="bottom_line alt-color cente" colspan="7">World Mod Editor</th>
				<tr>
				<td style="padding-top:5px;" colspan="7"></td>
				<tr>
                                <td style="width:2px;"></td> <!-- left spacer -->
				<td class="align-left" style="">World Name:</td>
				<td class="align-left" style="width:auto;"><?php echo $world ?></td>
                                <td class="center highlight-color" style="width:50px;">|</td> <!-- middle spacer -->
				<td class="align-left" style="margin-left:50px;">World Seed:</td>
                                <td class="align-left"><?php print getSeed($pdo,$world);?></td>
                                <td style="width:2px;"></td> <!-- right spacer -->
				<tr>
                                <td style="width:2px;"></td> <!-- left spacer -->
				<td class="align-left">Date Deployed:</td>
				<td class="align-left" style="width:auto;"><?php print getDateDeployed($pdo,$world);?></td>
                                <td class="center highlight-color" style="width:50px;">|</td> <!-- middle spacer -->
				<td class="align-left">Mods Selected:</td>
                                <td class="align-left"><?php print getSelectedModCountOfWorld($pdo,$world);?></td>
                                <td style="width:2px;"></td> <!-- right spacer -->
                                <tr>
                                <td style="width:2px;"></td> <!-- left spacer -->
                                <td class="align-left">Date Updated:</td>
                                <td class="align-left" style="width:auto;"><?php print getDateUpdated($pdo,$world);?></td>
                                <td class="center highlight-color" style="width:50px;padding-bottom:5px;">|</td> <!-- middle spacer -->
                                <td class="align-left">Mods Running:</td>
				<td class="align-left"><?php print getTotalModCountOfWorld($pdo,$world);?></td>
                                <td style="width:2px;"></td> <!-- right spacer -->
                        </table>
		      </div>

		      <div style="max-width:1600px;margin:auto;padding:10px;" class="">
			<table id="modtable" style="margin-top:45px !important;width:100%;" align=center border=0 id="edit_world" class="display outline">
				<thead>
					<th class="alt-color">Toggle</th>
					<th class="alt-color">Name</th>
					<th class="alt-color">Author</th>
					<th class="alt-color">Last Updated</th>
					<th class="alt-color">Version</th>
				</thead>
				<tbody>
					<?php echo '<script type="text/javascript">document.getElementById("spinner").style.display = "block";</script>'; ?>
					<?php echo '<script type="text/javascript">document.body.classList.add("noscroll");</script>'; ?>
					<?php #echo '<script type="text/javascript">document.body.classList.add("blur");</script>'; ?>
					<?php #echo '<script type="text/javascript">document.body.classList.add("overlay");</script>'; ?>
					<?php populateEnabledModList($pdo,$world,$getAllModsLatestVersion); ?>
					<?php populateDepModList($pdo,$world,$getAllModsLatestVersion); ?>
					<?php populateDisabledModList($pdo,$world,$getAllModsLatestVersion); ?>
				</tbody>
			</table>
		      </div>
			<table class="center" border=0>
				<td colspan=0 align=center>
					<a href='index.php'><button class="sm-bttn" type="button">Back</button></a>
					<button name='submit' id='submit_button' class="sm-bttn" type="submit" onClick='onSubmitClick();'>Save</button>
					<input type="text" value="<?php echo $world?>" name="world" hidden readonly></input>
				</td>
			</table>
		</form>
	      </div>
	</body>
</html>

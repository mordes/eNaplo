
<head>
    <link rel="stylesheet" href="komplexCSS.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
</head>

<?php
/**
 * Created by PhpStorm.
 * User: mordes
 * Date: 2017.02.18.
 * Time: 10:05
 */
 

if (!isset($_GET["osztalyok"]) || !isset($_GET["tanevek"]) || !isset($_GET["diakok"])) {
    header('Location: tanevvalaszto.php');
    exit();
}


include 'dbCommands.php';

//main
connect();


//Kapott érték
$osztaly = $_GET["osztalyok"];
$osztaly = mysqli_real_escape_string($conn, $osztaly); //ellenőrzi az átadott adat hitelességér -> nem lehet módositani a lekérdezést
$tanev = $_GET["tanevek"];
$tanev = mysqli_real_escape_string($conn, $tanev);
$diak = $_GET["diakok"];
$diak = mysqli_real_escape_string($conn, $diak); //ellenőrzi az átadott adat hitelességér -> nem lehet módositani a lekérdezést
	
	
// akciók végrehajtása
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
	global $osztaly, $tanev, $diak;
	
    switch ($_POST['akcio']) {
        case 'torles':
            $del = json_decode($_POST["torol"]);
            
            $idk = implode(",", $del);
            
            $sql = "DELETE FROM $tableJegy WHERE $tableJegy.id IN ($idk)";
            $conn->query($sql);
            break;
			
		case 'jegyfel':
            $jegy = json_decode($_POST["ujjegy"]);
			
			
			$sql = "SELECT id FROM evfolyam WHERE tanevid = $tanev AND diakid = $diak AND osztalyid = $osztaly"; //évfolyam id
			$result = mysqli_query($conn, $sql);
			if (mysqli_num_rows($result) > 0) {
				while ($row = mysqli_fetch_assoc($result)) {
					$megoldas = $row["id"];
				}
			}
			var_dump($jegy);
			
			//$sql = "INSERT INTO $tableJegy (evfolyamid, jegy, tanarid, tantargyid) VALUES ((string)$megoldas, (string)$jegy, (string)$jegy.tanarid, (string)$jegy.tantargyid);";
            //$conn->query($sql);
            break;

    }
    
}


selectYears();


disconnect();


function selectYears() {
    global $conn, $tableTantargy, $tableDiak, $tableEvfolyam, $tableTanev, $tableOsztaly, $tableSzak, $tableTantargySzak, $tableJegy, $osztaly, $tanev, $diak;


    echo "<div id='jegyek'>";



    
    //Kiválasztjuk a diák osztálya alapján a lehetséges tantárgyakat
    $sql = "SELECT $tableTantargy.nev, $tableTantargy.id
            FROM $tableDiak   
            INNER JOIN $tableEvfolyam ON $tableDiak.id = $tableEvfolyam.diakid
            INNER JOIN $tableTanev ON $tableEvfolyam.tanevid = $tableTanev.id
            INNER JOIN $tableOsztaly ON $tableEvfolyam.osztalyid = $tableOsztaly.id
            INNER JOIN $tableSzak ON $tableOsztaly.szakid = $tableSzak.id
            INNER JOIN $tableTantargySzak ON $tableSzak.id = $tableTantargySzak.szakid
            INNER JOIN $tableTantargy ON $tableTantargySzak.tantargyid = $tableTantargy.id
            WHERE $tableDiak.id = $diak AND $tableOsztaly.id = $osztaly AND $tableTanev.id = $tanev
            ORDER BY $tableTantargy.nev";
    echo "Jegyek...<br>";
    $result = $conn->query($sql);



    //hónapok kiirása
    $months = array("Szeptember", "Október", "November", "December",
        "Január", "Február", "Március", "Április", "Május", "Június");

    echo "<table style='width: 100%; table-layout: fixed;' border='1' cellpadding='1'>";
    echo "<tbody>";
    echo "<tr>";
    echo "<td class='month'></td>";

    foreach ($months as $value) {
        echo "<td class='month'>$value</td>";
    }

    echo "</tr>";

    //Kiirjuk az összes lehetséges tárgyát ebben a tanévben ehez az osztályhoz
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $str = $row["nev"];
            echo "<tr>";
            echo "<td class='month'>$str</td>";

            for ($x = 9; $x <= 12; $x++) { //hónapok végigjárása 9 azaz szeptembertől kezdve
                //IDE JÖNNEK A JEGYEK//
                $sql2 = "SELECT jegy.id, jegy.evfolyamid, jegy.jegy, DAY(jegy.datum) AS days, tanar.nev, jegy.tantargyid, jegy.tanarid
            FROM jegy INNER JOIN evfolyam ON jegy.evfolyamid = evfolyam.id
            INNER JOIN osztaly ON evfolyam.osztalyid = osztaly.id
            INNER JOIN diak ON evfolyam.diakid = diak.id
            INNER JOIN tanev ON evfolyam.tanevid = tanev.id
            INNER JOIN tantargy ON jegy.tantargyid = tantargy.id
            INNER JOIN tanar ON jegy.tanarid = tanar.id 
            WHERE $tableDiak.id = $diak AND $tableOsztaly.id = $osztaly AND $tableTanev.id = $tanev
                   AND $tableTantargy.id = {$row["id"]} AND MONTH($tableJegy.datum) = $x
            ORDER BY $tableJegy.datum";

                $jegylista = [];
                $jegyek = [];

                $result2 = $conn->query($sql2);
                if ($result2->num_rows > 0) {
                    while ($row2 = $result2->fetch_assoc()) {

                        $jegylista[] = $row2["jegy"];
                        $jegyek[] = $row2;
                    }
                }
                $jegylista = implode(",", $jegylista);
                $jegyek = json_encode($jegyek);
                echo "<td class='jegyek' onclick='jegyvalto(this, $jegyek);
                '>$jegylista</td>";
                if ($x == 12) {//ha elértük decembert akkor előről azaz 0
                    $x = 0;
                }
                if ($x == 6) {//ha június akkor vége
                    break;
                }
            }
            //


            echo "</tr>";
        }
    }
}

echo "<!-- The Modal -->";
echo "<div id='myModal' class='modal'>";

echo "<!-- Modal content -->";
echo "<div class='modal-content'>";
echo "<div class='modal-header'>";
echo "<span class='close'>&times;</span>";
echo "<h2>Jegyek</h2>";
echo "</div>";
echo "<div class='modal-body'>";
echo "</div>";

echo "<div class='modal-footer'>";
echo "<div id = 'felv' onclick='pajlada();'>Új jegy</div>";
echo "<div id = 'felvtorl' onclick='torles();'>Törlés</div>";
echo "</div>";

echo "<div class='modal-plus'><fieldset>";
echo "<legend id='legend-header'>Új jegy</legend>";
echo "<input type='number' name='quantity' min='1' max='5' id='felvevo'>";
echo "<input type='button' name='ujjegy' onclick='jegyfelvetel();' value='felvétel'></fieldset></div>";
echo "</div>";

echo "</div>";

echo "</tbody>";
echo "</table>";

echo "</div>";

?>

<body>
       
    
    <form style='display: none'  action="" method="POST">
        <input name="akcio" value="torles">
        <input name="torol">
    </form>
	
	<form style='display: none'  action="" method="POST">
        <input name="akcio" value="jegyfel">
        <input name="ujjegy">
    </form>
    
    <script type="text/javascript">

        function pajlada() {
            $(".modal-plus").css ({
                    "display": "block",
                    "-webkit-animation-name": "animatetop",
                    "-webkit-animation-duration": "1s",
                    "animation-name": "animatetop",
                    "animation-duration": "1s"
                });
        }

        function torles() {

            var checked = [];
            $("input[name='jegyek[]']:checked").each(function ()
            {
                checked.push(parseInt($(this).val()));
            });

            if (checked.length > 0) {
                if (confirm('A következő müvelet ' + checked.length + 'db jegyet fog törölni, biztosan törölni szeretné?')) {
                    
                    //TÖRLÉS
                    $del = checked;
					alert($del);
                    var torles = document.forms[0];
					
                    torles.torol.value = JSON.stringify($del);
                    torles.submit();
                } else {
                    return;
                }
            } else
                return;


        }
        
        function jegyfelvetel() {                   
			//JEGYFELVÉTEL
			$up = $("#felvevo");
			var felvetel = document.forms[1];
			felvetel.ujjegy.value = JSON.stringify($up);
			felvetel.submit();	
        }


        $modal = $('#myModal');
        $modalBody = $(".modal-body");

        function jegyvalto(valamit, jegyek) {

            //jegyekGlobal = jegyek;
			
            $modal.css("display", "block");

            $modalBody.empty();
            var htmlString = "";

            for (var jegy of jegyek) {
				alert(jegy.tantargyid);
                //alert("ASD: " + jegy.id);
                $modalBody.append("<div class='container'>");

                htmlString = jegy.jegy + "  " + jegy.days + " " + jegy.nev + " " + jegy.id + " " + jegy.evfolyamid + " " + jegy.tantargyid;
                $modalBody.append("<input type='checkbox' name='jegyek[]' value='" + jegy.id + "'/> " + htmlString + " <br />");

                $modalBody.append("</div>");

            }

        }


        $span = $(".close")[0];

        $span.onclick = function () {
            $modal.css("display", "none");
            $(".modal-plus").css("display", "none");
        }

        window.onclick = function (event) {
            if ($(event.target).get(0).id === 'myModal') {
                $modal.css("display", "none");
                $(".modal-plus").css("display", "none");
            }
        }

    </script>
</body>


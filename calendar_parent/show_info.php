<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

//INCLUDES 
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/my/lib.php');
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/calendar_month/block_calendar_month.php');

defined('MOODLE_INTERNAL') || die();


redirect_if_major_upgrade_required();

// Se necesita de una cuenta para acceder
require_login();

// Almacenamos la respuesta que recibamos del post enviado en el $script1 de "block_calendar_parent"
$response = file_get_contents('php://input');
//Pasamos el JSON a array de php mediante json_decode
$response = json_decode($response);
  
//Creamos la cabecera y reenviamos el contenido de get_json() pasando la respuesta
header('Content-Type: application/json');
echo json_encode(get_json($response));

//Función para crear el JSON de respuesta con la información
function get_json($response)
    {
// Creamos la nueva array donde almacenaremos los identificadores y nombres de los usuarios hijos/tutorados
    $nombresHijos = array();
    $identificadoresUser = array();

// Diferenciamos la respuesta (-1 se ha seleccionado la opción de todos los alumnos y otro valor es el identificador del hijo/alumno a mostrar)    
    if ( $response->userId == -1)
        {
        $usuariosHijos = get_child_users();

// Almacenamos cada identificador de los hijos recorriendo cada hijo y sacando el id y el nombre.
        $i = 0;
        foreach ($usuariosHijos as $Hijo) 
            {
            $nombresHijos[$i] = $Hijo->firstname . ' ' . $Hijo->lastname;    
            $identificadoresUser[$i++] = $Hijo->instanceid;
            }
        }
    else
        {
// Si es un identificador almacenamos solo el primer valor (en el array solo existe un hijo)            
        $nombresHijos[0] = $response->userName;
        $identificadoresUser[0] = $response->userId;
        }

// Empezamos a crear el JSON        
    $JSON = "[";

    $i = 0;
// Creamos un foreach por cada identificador para sacar las tasks de cada alumno    
    foreach ($identificadoresUser as $identify)
        {
// Añadimos una coma solo si no es la primera            
        if ($i != 0)
            $JSON .= ',';    
        $JSON .= '{"alumno":"' . $nombresHijos[$i]. '",';
        $JSON .= '"tasks":[';
// Almacenamos en una variable las entregas para el usuario        
        $tasks = get_user_tasks($identificadoresUser[$i++], $response->startDate, $response->finishDate);
// Hacemos un bucle ahora por cada entrega del alumno        
        $j = 0;
        foreach ($tasks as $task)
            {
// Añadimos una coma solo si no es la primera                   
            if ($j != 0)
                $JSON .= ',';
            $JSON .= '{';
//Creamos los campos clave:valor enviando la información correspondiente a la tarea                
            $JSON .= '"name":"' . $task->name . '",';            
            $JSON .= '"fecha":"' . $task->fecha . '",';
//En el caso de la fecha de entrega diferenciamos si es tipo string o no (por las comillas)            
            if($task->fechaentrega == "No entregado")
                $JSON .= '"fechaE":"' . $task->fechaentrega . '",';  
            else
                $JSON .= '"fechaE":' . $task->fechaentrega . ',';      
            $JSON .= '"grade":"' . $task->grade . '",';
            $JSON .= '"course":"' . $task->fullname . '"';
            $JSON .= '}';
            $j++;
            }
        $JSON .= ']}';
        }

    $JSON .= ']';
    
//Devolvemos el JSON
    return $JSON;    
    }    

//Función para obtener los usuarios hijo que estan asociados al padre
function get_child_users() 
    {
    global $CFG, $USER, $DB;

//Recuperamos los campos del usuario    
    $userfieldsapi = \core_user\fields::for_name();
    $allusernames = $userfieldsapi->get_sql('u', false, '', '', false)->selects;
//Hacemos un select de la información de los hijos del usuario actual enlazando los role_assignments, context y user.    
    if ($usercontexts = $DB->get_records_sql("SELECT c.instanceid, c.instanceid, $allusernames
                                               FROM {role_assignments} ra, {context} c, {user} u
                                               WHERE ra.userid = ?
                                               AND ra.contextid = c.id
                                               AND c.instanceid = u.id
                                               AND c.contextlevel = ".CONTEXT_USER, array($USER->id))) {
    }

    return $usercontexts;
    }

///////////////////////////////////////////////////////////////////////////////////////////////////////////
//CREO QUE TAMPOCO HACE FALTA    
//Función para extraer los cursos de un usuario por el identificador del usuario
/*function get_child_courses($id)
    {
    global $DB;
//Realizamos un select en el que extraemos todos los cursos asociados al identificador como parametro (del hijo)
    $sql = "SELECT c.id, c.fullname, c.shortname, c.summary, c.summaryformat, ue.timestart AS startdate, ue.timeend AS enddate FROM mdl_course c 
    JOIN mdl_user_enrolments ue
    JOIN mdl_user u ON u.id = ue.userid 
    JOIN mdl_enrol e ON e.id = ue.enrolid  
    WHERE u.id = $id 
    AND c.id = e.courseid";

//get_records_sql nos devuelve el resultado de la sentencia
    return $DB->get_records_sql($sql);
    }*/

// Funcion para devolver las tareas dado un usuario
function get_user_tasks($userid, $fechaIni, $fechaFin)
    {
    global $DB;

//Pasamos las fechas a UNIX    


//Sentencia para recuperar las tareas        
    $sql .= "SELECT a.name, a.duedate AS fecha, 
    (CASE
        WHEN an.timemodified  IS NULL THEN 'No entregado'
        ELSE an.timemodified 
    END) AS fechaEntrega, 
    (CASE 
        WHEN ag.grade = -1.00000 THEN 'Sin calificar'
        ELSE COALESCE(ag.grade, 'Sin calificar') 
    END) AS grade, 
    c.fullname
    FROM mdl_assign a
    LEFT JOIN mdl_course c ON a.course = c.id
    LEFT JOIN mdl_enrol e ON a.course = e.courseid
    LEFT JOIN mdl_user_enrolments ue ON (ue.userid = $userid AND ue.enrolid = e.id)
    LEFT JOIN mdl_assign_grades ag ON (ag.userid = $userid AND ag.assignment = a.id) 
    LEFT JOIN mdl_assign_submission an ON (an.userid = $userid AND an.assignment = a.id) 
    WHERE (ue.userid = $userid) ";

// Si se ha dado un valor en las fechas añadimos filtro por cada fecha
    if (!empty($fechaIni))
        $sql .= "AND a.duedate >= $fechaIni ";

    if (!empty($fechaFin))
        $sql .= "AND a.duedate <= $fechaFin ";
// Y lo ordenamos por fecha        
    $sql .= "ORDER BY fecha";
   
    //return $sql;
    return $DB->get_records_sql($sql);
    }
////////////////////////////////////////////////////////////////////////////////
//Esta por terminar    
// Función para obtener los quizzes
function get_quizzes($userid, $courseid)
    {
    global $DB;
        
//Sentencia para recuperar las tareas        
    $sql = "SELECT q.name, q.timeopen, q.timeclose, q.timelimit, qg.grade 
    FROM mdl_quiz q 
    INNER JOIN mdl_quiz_grades qg ON q.id = qg.quiz 
    WHERE q.course = $courseid 
    AND qg.userid = $userid";

    return $DB->get_records_sql($sql);
    }   
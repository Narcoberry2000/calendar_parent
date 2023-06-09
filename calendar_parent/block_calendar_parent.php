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

global $CFG;

class block_calendar_parent extends block_base 
  {

// Inicializamos el bloque.

  public function init() 
    {
    $this->title = get_string('pluginname', 'block_calendar_parent');
    }

// Función para mostrar los datos en el bloque    
  public function get_content() 
    {

    if ($this->content !== null)
      return $this->content;

// En primer lugar almacenamos los hijos en una variable
    $child_users = $this->get_child_users();
      
// Creamos la nueva array donde almacenaremos los identificadores de los usuarios hijos/tutorados
    $identificadoresUser = array();

// Almacenamos cada identificador de los hijos recorriendo cada hijo y sacando exclusivamente el id.
    $i = 0;
    foreach ($child_users as $child) 
      {
      $identificadoresUser[$i++] = $child->instanceid;
      }
      
// Función de Javascript para filtrar la información sobre las entregas y cursos
    $script1 = "<script>function filter_info()
      {
// Obtenemos el ID del select creado mas adelante
      sel = document.getElementById('myUserSelect');
// Recogemos los valores de los select y los almacenamos en variables      
      userId = sel.value;
      userName = sel.options[sel.selectedIndex].text;
      startDate = document.getElementById('myStartDate').value;
      finishDate = document.getElementById('myFinishDate').value;
//Para pasar a formato UNIX antes de enviarlo a show_info() ya con el formato adecuado      
      startDate = Date.parse(startDate);
      finishDate = Date.parse(finishDate);
      startDate = startDate.toString().substring(0, 10);
      startDate = parseInt(startDate);
      finishDate = finishDate.toString().substring(0, 10);
      finishDate = parseInt(finishDate);

      
// Enviamos estos parametros a la pagina show_info.php
      var url = '$CFG->wwwroot/moodle/blocks/calendar_parent/show_info.php';
      var data = {userName: userName, userId: userId, startDate: startDate, finishDate: finishDate};

// Realizamos el envío tipo post enviando data en formato JSON
      fetch(url, {
        method: 'POST',
        body: JSON.stringify(data), // data can be `string` or {object}!      
        headers:{
          'Content-Type': 'application/json'
        }
      }).then(res => res.json())
      .catch(error => console.error('Error:', error))
// Si no hay errores en la respuesta, se llama al script show_info      
      .then(response => show_info(response));           
      }    
      </script>";

// Creamos un segundo script llamado show_info en el que enviamos lo que se reciba de show_info.php para visualizar la información    
    $script2 = "<script>function show_info(response)
      {
// Pasamos la respuesta JSON a objeto        
      respuesta = JSON.parse(response);

      currentDiv = document.getElementById('info_parent');
      // Borra todos los hijos del div
      while (currentDiv.hasChildNodes()) {
        currentDiv.removeChild(currentDiv.firstChild);
      }
// Realizamos un forEach para recorrer la respuesta 

      
      currentDiv.innerHTML = '';
      
      respuesta.forEach(function(user) 
        {
        
          if(user.tasks.length == 0)
          {
          currentDiv.innerHTML += '<h6>No hay tareas para mostrar  '+ user.alumno +'</h6>';
          }
          else
          {
// Creamos un div nuevo y un titulo para crear un espacio para el alumno/hijo          
          newDiv = document.createElement('div');
          newDiv.classList.add('myDIV');
          newDiv.innerHTML += '<h4>'+user.alumno+'</h4>';
//          currentDiv = document.getElementById('info_parent');          
          currentDiv.appendChild(newDiv);
// Creamos la primera columna de contenidos por usuario
          let table = document.createElement('table');
          table.classList.add('myTable');
          let thead = document.createElement('thead');
          let tbody = document.createElement('tbody');
          table.appendChild(thead);
          table.appendChild(tbody);
          newDiv.appendChild(table);

// Esta primera columna es por defecto y sirve para saber que contenidos se muestran por columna          
          let row = document.createElement('tr');
          let name = document.createElement('th');
          name.innerHTML = 'Nombre Entrega';
          let course = document.createElement('th');
          course.innerHTML = 'Curso';
          let fecha = document.createElement('th');
          fecha.innerHTML = 'Fecha limite';
          let fecha_entrega = document.createElement('th');
          fecha_entrega.innerHTML = 'Fecha de entrega';
          let nota = document.createElement('th');
          nota.innerHTML = 'Nota';

// Insertamos los datos a la columna          
          row.appendChild(name);
          row.appendChild(course);
          row.appendChild(fecha);
          row.appendChild(fecha_entrega);
          row.appendChild(nota);

// Insertamos la columna a la tabla (TableHead)          
        thead.appendChild(row);
      

// Realizamos un segundo forEach para las tasks de cada alumno          
        user.tasks.forEach(function(task) 
          {
// Creamos una fila en la que pasamos la información recogida en el objeto            
          let rows = document.createElement('tr');
          let name = document.createElement('td');
          name.innerHTML = task.name;
          let course = document.createElement('td');
          course.innerHTML = task.course;
          let fecha = document.createElement('td');
          fecha.innerHTML = new Date(task.fecha * 1000).toLocaleDateString();
          let fecha_entrega = document.createElement('td');
          if(task.fechaE == 'No entregado')
            {
            fecha_entrega.innerHTML = task.fechaE;
            }
          else
            {
              fecha_entrega.innerHTML = new Date(task.fechaE * 1000).toLocaleDateString();
            }
          let nota = document.createElement('td');
          nota.innerHTML = task.grade;

          rows.appendChild(name);
          rows.appendChild(course);
          rows.appendChild(fecha);
          rows.appendChild(fecha_entrega);
          rows.appendChild(nota);
          thead.appendChild(rows);
          });
         }
        });
      
      }
      </script>";
// Creamos unas etiquetas de estilos para todo el HTML
    $style = "<style>
    .myDIV 
    { 
    margin-bottom: 50px;
    }
  .myTable
    {
    border-collapse: collapse;
    border-spacing: 0;
    display: flex;
    justify-content: center; 
    height:200px;
    overflow:scroll;
    overflow-x: hidden;  
    } 
// Estilos para el scroll de la tabla   
  .myTable::-webkit-scrollbar 
    {
    display: none;
    width: 10px;
    height: 10px;
    background-color: lightblue;
    }
  ..myTable:hover::-webkit-scrollbar 
    {
    display: initial;  
    }
  .myTable::-webkit-scrollbar-thumb 
    {
    background-color: #09C;
    }
  .myTable th 
    {
    padding: 10px 20px;
    scroll-behavior: auto;
    background-color: #04AA6D;
    color: white; 
//Para fijar el tableHeader y que el scroll no le afecte        
    position: sticky;
    top: 0;
    }
  #info_parent 
    {
    width: 100%;
    text-align: center;
    justify-content: center; 
    margin-top: 40px;
    }
  .divSelect 
    {
    display: flex;
    padding: 10px;
    display: flex;
    align-items: center;
    justify-content: space-evenly;

    }
  .divUser div
    {
    margin-right: 10px; 
    margin-left: 10px;
    padding: 5px 10px;
    display: inline;


    }
// Estilos para los inputs de las fechas        
  .divSelect input[type='date'] 
    {
    flex-grow: 1; // Permite que los inputs de tipo date se expandan para llenar el espacio disponible
    flex-shrink: 0;// Evita que los inputs de tipo date se reduzcan más de lo normal
    margin-right: 20px; 
    margin-left: 20px;
    }
  .divSelect button
    {
    padding: 5px 10px;

    }        
                </style>";

// Creamos la estructura de contenido dentro del bloque en HTML                
    $div = '<div>';
// Creamos el contenedor para los selects    
    $divSelect = '<div class="divSelect">'; 

// El boton con onClick a la función filter_info (En la variable $script1)    
    $select_button = '<button type="button" style="float: right" onclick="filter_info()">' . get_string("ButtonText", "block_calendar_parent") . '</button>';

// Creación de la opción para todos los usuarios    
      $select_user_options = '<option value="-1">' . get_string("CalendarParentStudentFirstOption", "block_calendar_parent") . '</option>'; 

// Creamos un option por cada usuario que este relacionado con el hijo      
      foreach ($child_users as $child_user) 
        { 
        $select_user_options .= '<option value="' . $child_user->instanceid . '">' . $child_user->firstname. ' '. $child_user->lastname. '</option>';            
        }
// Creamos un div para meter el texto y el select
      $divUserName = '<div class="divUser">'; 
      $select_user_html = '<select id="myUserSelect"name="selector1">'.$select_user_options."</select>"; 
      $divUserNameEnd = '<div>';      
      
// Creamos un div para meter el texto y el select
      $divDate1 = '<div class="divDate1">'; 
      $firstDate = get_string('firstDate', 'block_calendar_parent') . ": "; 
      $select_fecha_inicio = '<input type="date" id="myStartDate" name="trip-start">';// Si no tiene value no devuelve nada
      $divDate1End = '<div>';   

// Creamos un div para meter el texto y el select
      $divDate2 = '<div class="divDate2">'; 
      $secondDate = get_string('secondDate', 'block_calendar_parent') . ": "; 
      $select_fecha_final = '<input type="date" id="myFinishDate" name="trip-finish">';
      $divDate2End = '<div>';
// Finaliza el contenedor de los select
      $divSelectEnd = '</div><br/>'; 

// Creación del contenedor para mostrar la infomación filtrada por los select
      $texto = '<div id="info_parent"></div>';

      $div_end = '</div>'; 
// Recogemos el contenido
      $this->content = new stdClass;
      $this->content->text = $script1 . $script2 .$style . $div .$divSelect . $divUserName . $UserName . $select_user_html . $divUserNameEnd;
      $this->content->text .= $divDate1 . $firstDate . $select_fecha_inicio . $divDate1End . $divDate2 . $secondDate;
      $this->content->text .= $select_fecha_final . $divDate2End . $select_button  . $divSelectEnd . $texto . $div_end;
// Enviamos el contenido       
      return $this->content;
    }

// Función para obtener los usuarios hijo que estan asociados al padre
function get_child_users() 
  {
  global $CFG, $USER, $DB;
    
// Recuperamos los campos del usuario         
  $userfieldsapi = \core_user\fields::for_name();
  $allusernames = $userfieldsapi->get_sql('u', false, '', '', false)->selects;
// Hacemos un select de la información de los hijos del usuario actual enlazando los role_assignments, context y user. 
  if ($usercontexts = $DB->get_records_sql("SELECT c.instanceid, c.instanceid, $allusernames
                                                FROM {role_assignments} ra, {context} c, {user} u
                                               WHERE ra.userid = ?
                                                     AND ra.contextid = c.id
                                                     AND c.instanceid = u.id
                                                     AND c.contextlevel = ".CONTEXT_USER, array($USER->id))) {
    }
    
      return $usercontexts;
      }
}  
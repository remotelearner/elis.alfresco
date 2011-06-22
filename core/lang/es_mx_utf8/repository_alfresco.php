<?php

$string['adminusername'] = 'Desautoriza nombre de usuario administrativo';
$string['alfheader'] = 'Alfresco filtros multimedia';
$string['alfheaderintro'] = 'Para personalizar las dimensiones de medios añade &d=ANCHOxALTO hasta el final de la URL. Anchura y la altura también aceptan un porcentaje.';
$string['alfmediapluginavi'] = 'Habilitar .avi filtro';
$string['alfmediapluginflv'] = 'Habilitar .flv filtro';
$string['alfmediapluginmov'] = 'Habilitar .mov filtro';
$string['alfmediapluginmp3'] = 'Habilitar .mp3 filtro';
$string['alfmediapluginmpg'] = 'Habilitar .mpg filtro';
$string['alfmediapluginram'] = 'Habilitar .ram filtro';
$string['alfmediapluginrm'] = 'Habilitar .rm filtro';
$string['alfmediapluginrpm'] = 'Habilitar .rpm filtro';
$string['alfmediapluginswf'] = 'Habilitar .swf filtro';
$string['alfmediapluginswfnote'] = 'Como medida de seguridad predeterminada, los usuarios normales no se debe permitir que incorporar archivos SWF de Flash.';
$string['alfmediapluginwmv'] = 'Habilitar .wmv filtro';
$string['alfmediapluginyoutube'] = 'Habilitar el filtro de enlace de YouTube';
$string['alfrescosearch'] = 'Búsqueda de Alfresco';
$string['badxmlreturn'] = 'Mal XML retorno';
$string['cachetime'] = 'Caché de los archivos';
$string['categoryfilter'] = 'Filtro de categoría';
$string['choosealfrescofile'] = 'Elija el archivo de Alfresco';
$string['choosefrommyfiles'] = 'Elija entre mis archivos';
$string['chooselocalfile'] = 'Elija el archivo local';
$string['chooserootfolder'] = 'Seleccione la carpeta raíz';
$string['configadminusername'] = 'Alfresco tiene un nombre de usuario predeterminada del <b>administrador</ b>. Moodle también'.
                                 'usará un valor predeterminado ' .
                                 'nombre de usuario del administrador de la primera cuenta que usted cree. Tendremos que'. 
								 'volver a maparlo ' .
                                 'valor a algo más al crear la cuenta de Alfresco para ese usuario. <br /> <br />' .
                                 'El valor que especifique aquí <b>debe</ b> ser único para su sitio Moodle. No se puede tener ' .
                                 'una cuenta de usuario de Moodle con el valor de nombre de usuario que entran y que debe'. 
								 'garantizar que una no se crea después de este valor se ha fijado.';
$string['configadminusernameconflict'] = 'The username override that you have set for your Moodle <b>admin</b> account: ' .
                                         '<i>$a->username</i> has already been used to create an Alfresco account.<br /><br />' .
                                         '<b>WARNING: A Moodle account with that username has been created which will directly ' .
                                         'conflict with the Alfresco account.  You must either delete or change the username ' .
                                         'of the <a href=\"$a->url\">Moodle user</a>.</b>';
$string['configadminusernameset'] = 'EL desautoriza nombre de usuario que ha establecido para su Moodle <b> admin </ b> en cuenta: ' .
                                    '<i>$a</i> ya se ha utilizado para crear una cuenta de Alfresco.';
$string['configcachetime'] = 'Especifica que los archivos del repositorio se almacenan en caché durante todo este tiempo adentro del navegador del usuario';
$string['configurecategoryfilter'] = 'Configurar archivo de catagoria';
$string['configdefaultfilebrowsinglocation'] = 'Si elige un valor aquí será la ubicación predeterminada que un usuario ' .
                                               'encuentra se envía automáticamente a la hora de lanzar un navegador de archivos ' .
                                               'sin tener una ubicación anterior para ser enviados.<br /><br /><b>NOTE:</b> ' .
                                               'Si un usuario no tiene permisos para ver la ubicación predeterminada, el ' .
                                               'verá la siguiente ubicación disponible en la lista que se ha ' .
                                               'permisos para ver.';
$string['configdeleteuserdir'] = 'Al borrar una cuenta de usuario de Moodle, en caso de que el usuario tenga una cuenta de'. 
                                 'Alfresco, será borrado al mismo tiempo' .
                                 'Por predeterminada su directorio de Alfresco no se eliminarán. ' .
                                 'Cambiar esta opción para habilitar o deshabilitar que el comportamiento.<br /><br /><b>NOTE:</b> ' .
                                 'borrar un directorio principal del usuario en Alfresco se romperá todos los enlaces a '.                                 'contenido en Moodle ' .
                                 'que se encuentra en ese directorio.';
$string['configuserquota'] = 'Establecer el valor predeterminado de la cantidad de espacio de almacenamiento que todos los usuarios'.                             'de Moodle en Alfresco puede utilizar.  ' .
                             '<b>Seleccione Ilimitada para espacio de almacenamiento ilimitado.</b>';
$string['couldnotaccessserviceat'] = 'No se pudo acceder al servicio de Alfrecso a: $a';
$string['couldnotdeletefile'] = '<br />Error: No se puede eliminar: $a';
$string['couldnotgetalfrescouserdirectory'] = 'No se pudo obtener el directorio de usuario de Alfresco para el usuario: $a';
$string['couldnotgetfiledataforuuid'] = 'No se pudo obtener datos del archivo para UUID: $a';
$string['couldnotgetnodeproperties'] = 'No se pudo obtener las propiedades de nodo para UUID: $a';
$string['couldnotmigrateuser'] = 'No se puede migrar cuenta de usuario para: $a';
$string['couldnotmovenode'] = 'No se puede mover el nodo a la nueva ubicación';
$string['couldnotmoveroot'] = 'No se pudo mover el contenido de la carpeta raíz hasta la nueva ubicación';
$string['couldnotopenlocalfileforwriting'] = 'No se pudo abrir el archivo local para escribir: $a';
$string['couldnotwritelocalfile'] = 'No se pudo escribir el archivo local';
$string['defaultfilebrowsinglocation'] = 'Ubicación predeterminado del archivo de navegación ';
$string['deleteuserdir'] = 'Auto-eliminar directorios de usuario de Alfresco';
$string['description'] = 'Conectar con el sistema de gestión de Alfresco repositorio de documentos.';
$string['done'] = 'Terminado';
$string['errorcouldnotcreatedirectory'] = 'Error: no se pudo crear el directorio $a';
$string['errordirectorynameexists'] = 'Error: El directorio de $a que ya existe';
$string['erroruploadduplicatefilename'] = 'Error: Un archivo con ese nombre ya existe en este directorio: <b>$a</b>';
$string['erroruploadquota'] = 'Error: No tiene suficiente espacio de almacenamiento para cargar este archivo.';
$string['erroruploadquotasize'] = 'Error: No tiene suficiente espacio de almacenamiento para cargar este archivo.  Ha utilizado ' .
                              '$a->current de un máximo de $a->max';
$string['erroropeningtempfile'] = 'Error al abrir el archivo temporario';
$string['errorreadingfile'] = 'Error al leer el archivo del repositorio: $a';
$string['errorreceivedfromendpoint'] = 'Alfresco: Error recibido de punto final -- ';
$string['erroruploadingfile'] = 'Error al subir archivo a Alfresco';
$string['failedtoinvokeservice'] = 'No se ha podido invocar el servicio de $a->serviceurl Código: $a->code';
$string['filealreadyexistsinthatlocation'] = '$a archivo ya existe en ese lugar';
$string['incorectformatforchooseparameter'] = 'Formato incorrecto para el parámetro de elegir';
$string['installingwebscripts'] = 'Instalación de secuencias de comandos nueva web, por favor espere...';
$string['invalidcourseid'] = 'Identificación de curso no es válido: $a';
$string['invalidpath'] = 'La ruta del repositorio no es válida';
$string['invalidschema'] = 'esquema no es válido $a';
$string['invalidsite'] = 'El sitio no es válido';
$string['lockingdownpermissionson'] = 'Bloqueando los permisos de carpeta de Alfresco <b>$a->name</b> (<i>$a->uuid</i>)';
$string['myfiles'] = 'Mis archivos';
$string['myfilesquota'] = 'Mis archivos - $a libre';
$string['nocategoriesfound'] = 'No hay categorías que se encuentran';
$string['processingcategories'] = 'Procesando categorías...';
$string['quotanotset'] = 'No establecido';
$string['quotaunlimited'] = 'Ilimitado';
$string['repository'] = 'Alfresco';
$string['repository_alfresco_category_filter'] = 'Elija las categorías disponibles al filtrar los resultados de la búsqueda';
$string['repository_alfresco_root_folder'] = 'La carpeta raíz en el repositorio donde este sitio Moodle guardarálos adentro de los archivos de Alfresco';
$string['repository_alfresco_server_homedir'] = 'Este es el directorio de origen (en relación con el espacio raíz del repositorio) para el usuario configurado para acceder a Alfresco sin provocar barra (/).< br /> <br /> Ejemplos: <br /> <br /> <b> my_home_dir Moodle usuario / usuario A </ b>';
$string['repository_alfresco_server_host'] = 'La dirección URL de su servidor de Alfresco (debe estar en el siguiente formato http://www.myserver.org).';
$string['repository_alfresco_server_password'] = 'La contraseña para entrar al servidor de Alfresco.';
$string['repository_alfresco_server_port'] = 'El puerto que el servidor de Alfresco se está ejecutando en(i.e. 80, 8080).';
$string['repository_alfresco_server_settings'] = 'Alfresco configuración del servidor';
$string['repository_alfresco_server_username'] = 'El nombre de usuario para iniciar sesión en el servidor de Alfresco.';
$string['repositoryname'] = 'Alfresco';
$string['resetcategories'] = 'Restablecer categorías';
$string['resetcategoriesdesc'] = 'Esto obligará a una actualización de todas las categorías desde el repositorio (nota: esto probablemente tardará unos 30-60 segundos para completar)';
$string['rootfolder'] = 'La carpeta raíz';
$string['serverpassword'] = 'Contraseña';
$string['serverport'] = 'Puerto';
$string['serverurl'] = 'URL';
$string['serverusername'] = 'Nombre de usuario';
$string['startingalfrescocron'] = 'Iniciando Alfresco cron...';
$string['startingpartialusermigration'] = 'Iniciando migración de usuarios parcial...';
$string['unabletoauthenticatewithendpoint'] = 'Alfresco: No se puede autenticar con el punto final';
$string['userquota'] = 'Usuario cuota de almacenamiento';
$string['uploadedbymoodle'] = 'Cargado por Moodle';
$string['usernameorpasswordempty'] = 'Nombre de Usuario y / o la contraseña está vacía';
$string['youdonothaveaccesstothisfunctionality'] = 'Usted no tiene acceso a esta funcionalidad';

?>
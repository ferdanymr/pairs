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

/**
 * Plugin strings are defined here.
 *
 * @package     mod_taller
 * @category    string
 * @copyright   2021 Fernando Munoz <fernando_munoz@cuaieed.unam.mx>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename']            = 'Taller';
$string['pluginname']            = 'Taller';
$string['modulenameplural']      = 'Taller';
$string['tallername']   = 'Nombre del taller';
$string['ajustescalif']          = 'Ajustes de calificación';
$string['estrategia']            = 'Estrategia de calificación';
$string['estrategia_help']       = 'Rúbrica: Se realiza una valoración del nivel respecto a los criterios especificados.';
$string['rub']                   = 'Rúbrica';
$string['calif_env']             = 'Calificación por el envio';
$string['calif_env_help']        = 'Esta configuración especifica la calificación maxima que se puede obtener en los trabajos enviados.';
$string['calif_aprob']           = 'Calificación aprobatoria de envio';
$string['calif_aprob_help']      = 'Esta configuración determina la calificación mínima requerida para pasar.';
$string['calif_val']             = 'Calificación por valoración';
$string['calif_val_help']        = 'Esta configuración determina la calificación maxima que puede obtenerse en la valoración de un trabajo enviado.';
$string['calif_aprob_val']       = 'Calificación aprobatoria de valoración';
$string['calif_aprob_val_help']  = 'Esta configuración determina la calificación mínima requerida para pasar.';
$string['no_decimales']          = 'Posiciones decimales en las calificaciones';
$string['param_env']             = 'Parámetros de los envíos';
$string['param_inst']            = 'Instrucciones para el envío';
$string['param_max']             = 'Número máximo de archivos adjuntos por envío';
$string['param_type_arch']       = 'Tipos de archivos permitidos como anexos a envíos';
$string['param_type_arch_help']  = 'Los tipos de archivos permitidos para el envio pueden restringirse al proporcionar una lista de los tipos de archivos permitidos. Si el campo se deja vacío, entonces todos de arvhivos estan permitidos.';
$string['param_tam_max']         = 'Tamaño máximo del anexo del envío';
$string['conf_val']              = 'Configuración de la valoración';
$string['conf_val_inst']         = 'Instrucciones para la valoración';
$string['retro']                 = 'Retroalimentación';
$string['retro_con']             = 'Conclusión';
$string['no_revisiones']         = 'Numero de revisiones';
$string['no_revisiones_help']    = 'Este parametro indica el numero de revisiones que tiene que recibir un alumno una ves que envie su trabajo';
$string['criterio']              = 'Criterio';
$string['descrip']               = 'Descripción';
$string['calif_def']             = 'Calificación de nivel y definición';
$string['envio']                 = 'Envío';
$string['titulo']                = 'Título';
$string['contenidoenvio']        = 'Contenido del envío';
$string['num_max_arch']          = 'Número máximo de archivos adjuntos por envío';
$string['adjunto']               = 'Adjunto';
$string['coment']                = 'Comentario';
$string['noenvio']               = 'No se ha registrado ningun archivo';
$string['addenvio']              = 'Añadir envio';
$string['setenvio']              = 'Editar envio';
$string['setcriterios']          = 'Modificar criterios';
$string['successenvio']          = 'a sido registado con exito';
$string['verenvio']              = 'Ver envio';
$string['deletenvio']            = 'Eliminar envio';
$strong['q_evaluate_alum']       = '¿Estas seguro de pasar a evaluar alumnos?';
$string['adver_evaluar_alumn']   = 'Una vez que empieces a evaluar a tus compañeros tu trabajo no va poder ser modificable de ninguna manera';
$string['info_envio']            = 'Una vez realizado el envio, puedes proceder a evaluar a tus compañeros';
$string['info_calif']            = 'Cuando obtenga y realice el numero de evaluaciones que se requiere se le mostrara su calificacion';
$string['evaluarJob']            = 'Evaluar trabajo';
$string['calif_final']           = 'Su calificacion final es:';
$string['calif_ot_env']          = 'Calificar otros envios';
$string['instruc_evaluacion']    = 'Instrucciones evaluacion';
$string['evaluate_done']         = 'Evaluaciones realizadas';
$string['calificacion']          = 'Calificación';
$string['no_env']                = 'De momento no hay envios para evaluar vuelve un poco más tarde';
$string['download_arch']         = 'Descarga aquí el archivo a evaluar';
<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // To restore the exported production data, uncomment the line below:
        // $this->call(ProductionDataSeeder::class);

        // Standard seeders:
        // 1. Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@escuela.edu.mx'],
            [
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'name' => 'Admin Sistema',
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role' => 'ADMIN',
                'status' => 'ACTIVE',
                'email_verified_at' => now(),
            ]
        );

        // 2. Create Active Cycle
        $cycle = \App\Models\Cycle::firstOrCreate(
            ['name' => '2024-2025'],
            [
                'start_date' => '2024-08-26',
                'end_date' => '2025-07-16',
                'is_active' => true,
            ]
        );

        // 3. Create Sample Group
        \App\Models\ClassGroup::firstOrCreate(
            [
                'cycle_id' => $cycle->id,
                'grade' => '1',
                'section' => 'A',
            ],
            [
                'id' => \Illuminate\Support\Str::uuid()->toString(),
            ]
        );

        // 4. Seeding Infractions
        $infractions = [
            ['id' => 1, 'description' => 'Incumplimiento de tareas, proyectos, etc.', 'severity' => 'NORMAL', 'created_at' => '2025-12-09 19:36:27'],
            ['id' => 2, 'description' => 'Portar celular dentro del salón.', 'severity' => 'NORMAL', 'created_at' => '2025-12-09 19:36:27'],
            ['id' => 3, 'description' => 'Corte de pelo, fuera de lo indicado en el reglamento.', 'severity' => 'GRAVE', 'created_at' => '2025-12-09 19:36:27'],
            ['id' => 4, 'description' => 'Llegar después de horario de entrada.', 'severity' => 'NORMAL', 'created_at' => '2025-12-09 19:36:27'],
            ['id' => 5, 'description' => 'Faltas sin justificar.', 'severity' => 'GRAVE', 'created_at' => '2025-12-09 19:36:27'],
            ['id' => 6, 'description' => 'No atender indicaciones dentro del salón.', 'severity' => 'NORMAL', 'created_at' => '2025-12-10 16:25:20'],
            ['id' => 7, 'description' => 'Faltar el respecto al maestro (a).', 'severity' => 'NORMAL', 'created_at' => '2025-12-10 16:29:07'],
            ['id' => 8, 'description' => 'Agredir verbalmente a un compañero.', 'severity' => 'NORMAL', 'created_at' => '2025-12-10 16:29:21'],
            ['id' => 9, 'description' => 'No entrar en clase.', 'severity' => 'NORMAL', 'created_at' => '2025-12-10 16:29:37'],
            ['id' => 10, 'description' => 'Portar objectos que no son de su propiedad.', 'severity' => 'NORMAL', 'created_at' => '2025-12-10 16:29:56'],
            ['id' => 11, 'description' => 'Portar objectos que pueden alterar la salud física o mental de compañeros y maestros.', 'severity' => 'NORMAL', 'created_at' => '2025-12-10 16:30:12'],
            ['id' => 12, 'description' => 'Portar sustancias nocivas en si mochila o vestimenta.', 'severity' => 'NORMAL', 'created_at' => '2025-12-10 16:30:49'],
            ['id' => 13, 'description' => 'Traer Alimentos no permitidos.', 'severity' => 'NORMAL', 'created_at' => '2025-12-10 16:31:04'],
            ['id' => 14, 'description' => 'Falta de higiene personal.', 'severity' => 'NORMAL', 'created_at' => '2025-12-10 16:31:18'],
            ['id' => 15, 'description' => 'Uñas largas.', 'severity' => 'NORMAL', 'created_at' => '2025-12-10 16:31:30'],
            ['id' => 16, 'description' => 'Falta de cinto (hombres).', 'severity' => 'NORMAL', 'created_at' => '2025-12-10 16:31:45'],
            ['id' => 17, 'description' => 'Falta de moño (mujeres).', 'severity' => 'NORMAL', 'created_at' => '2025-12-10 16:32:02'],
            ['id' => 18, 'description' => 'No traer su Cuadernillo de Tutoría.', 'severity' => 'NORMAL', 'created_at' => '2025-12-10 16:32:19'],
            ['id' => 19, 'description' => 'No traer firmado aviso de maestro, Perfecto o Dirección.', 'severity' => 'NORMAL', 'created_at' => '2025-12-10 16:32:36'],
            ['id' => 20, 'description' => 'Uniforme no adecuado (pantalón, jomper, blusa, etc.) maltratado, o que no corresponde a las características mencionadas en el reglamento escolar. ', 'severity' => 'NORMAL', 'created_at' => '2025-12-10 16:32:50'],
        ];

        foreach ($infractions as $infraction) {
            \App\Models\Infraction::firstOrCreate(['id' => $infraction['id']], $infraction);
        }

        // 5. Seeding Regulations
        \App\Models\Regulation::firstOrCreate(
            ['id' => 1],
            [
                'title' => 'Reglamento Escolar General',
                'content' => '<h1 class="ql-align-center">Escuela Secundaria General No.5</h1><h2 class="ql-align-center">“Dr. Rogelio Montemayor Seguy”</h2><p><strong>Reglamento Interno para alumnos</strong></p><p><strong>I. Asistencia y puntualidad</strong></p><ol><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Llegar puntualmente, la hora de entrada para el turno matutino es 7:20 am y la salida es a la 1:15 pm. El portón se cierra a la 7: 30 am</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Para el turno vespertino la entrada es a la 1:20 pm y la salida es a la 7:15 de la tarde. El portón se abre a las 1:10 para el TV.</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Solo se justifica casos de enfermedad, accidentes y la muerte de familiares directos. Las demás situaciones son responsabilidad de los padres y aparecerá la falta (también se aplica en caso de que se retire un alumno, para evitar el salir a cualquier hora).</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>En temporada de frio y lluvia, los alumnos pasan directamente al salón de clases.</li></ol><p><strong>II. Cuidado de recursos materiales</strong></p><ol><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Respetar y cuidar el mobiliario, recursos materiales de los salones, patios y jardines. Los alumnos que destruyan material, vidrio, barandales, cortinas, tuberías de agua, rayar butacas etc. Pagaran el daño.</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Depositar la basura en los botes y cestos de cada salón. Las botellas de plástico se reciclan en las canastillas negras de cada salón. No tirar envolturas de alimentos ni botellas en los jardines o patios. (somos una comunidad escolar que recicla reúsa y separa la basura).</li></ol><p><strong>III. Presentación Personal</strong></p><ol><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Tanto en hombres y mujeres es necesario el baño diario, corte de uñas, no se permite depilación de cejas. Se recomienda uso de desodorante, traer limpio y completo su uniforme. Solo en educación física pueden traer tenis obscuros (negro o gris), calcetas blancas al tobillo, no tines (hombres). Portar permanentemente su identificación personal o gafete desde antes de entra a la escuela y diariamente.</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>En el caso de hombres el corte de pelo natural no se permiten cortes de pelo con rayas ni copetes. No traer aretes.</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>En el caso de mujeres el pelo totalmente recogido limpio, sin piojos con su moño. Sin pintura en labios y cejas , aretes apropiados a su edad, la falda debajo de su rodilla.</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>En temporada de frio el uso del uniforme deportivo es obligatorio. chaquetin con escudo y pants gris oficial de la escuela. El uniforme deberá traerlo desde el primer día de clases.</li></ol><p><strong>IV. Convivencia escolar</strong></p><ol><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Mantener buenas relaciones con maestros y entre compañeros. En caso de cualquier tipo de agresión que sea denunciada se mandara a llamar a los padres de los alumnos. Se aplica sanción correspondiente de acuerdo con la falta o gravedad de la situación.</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Evitar manifestaciones afectivas en parejas de novios (besos e la boca, abrazarse o tomarse de la mano dentro de las instalaciones de la escuela o sus alrededores)</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Usar un lenguaje adecuado para comunicarse evitando burlas o apodos entre compañeros</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Las denuncias de agresión por celular deberán ser solucionadas por padres de, ya que ellos proporciona el teléfono sus hijos y deben vigilar su uso.</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Queda prohibido portar y usar celular en la institución; o de lo contrario, la escuela actuara conforme al protocolo de convivencia y no se hace responsable por perdida de este.</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Uso de instalaciones del salón de computación:</li><li data-list="bullet" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Las computadoras del taller solamente pueden ser utilizadas con autorización del maestro o maestra en horas de clase. Dentro de la clase no se  permite estar sacando fotos, hacer grabaciones o enviar mensajes, descargar juegos o actividades que sean diferentes del objetivo de los temas .</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Evitar desconectar cables y destruir los teclados. El uso inadecuado  de las computadoras y que sea reportado será motivo de sanción y reposición de los materiales.</li></ol><p><strong>V. cumplimiento de actividades escolares </strong></p><ol><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Traer el material revisando su horario con anterioridad. No se permite el teléfono para pedir tareas.</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>No se permite salir a la papelería durante el horario de clase.</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>No se permite imprimir tareas o trabajos durante clases.</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Respetar el horario de la clase y no interrumpir a los maestros en sus clases para entregar tareas.</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Situaciones especiales. En caso de padecer algún problema de salud comunicarlo a la dirección, con documento que lo compruebe, ya sea por discapacidad o enfermedad crónica.</li></ol><p class="ql-align-justify"><strong>VI. cuidado de libros y cuadernos</strong></p><ol><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Los libro y cuadernos de cada alumno deben estar forrados y etiquetados con su nombre completo, grado sesión y escudo de la escuela, no se reponen libros perdidos.</li></ol><p class="ql-align-justify"><strong>VII. revisión de mochila</strong></p><ol><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Por ordenes de la secretaria de la educación se revisará continuamente la mochila a sus hijos por lo que se les pide evitar traer comida no permitida: fritos, bebidas energizantes, chetos, chicles, chocolates, sustancias nocivas a la salud. Además, no traer plumas, marcadores de tinta permanente. Solo deben traer su lápiz, colores de palo o cera.</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>A los alumnos que se les encuentre sustancias tóxicas serán canalizados a PRONNIF aplicando el protocolo que marca la secretaria de educación, será necesario realizar un antidoping presentar sus resultados de atención cada semana durante toda su estancia escolar.</li></ol><p><strong>VIII. Participación de padres de familia</strong></p><ol><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Los padres de familia deben estar al pendiente de su hijo para observar su desempeño escolar el cual se dará a conocer en su cuaderno de tutoría. Dicho cuadernillo servirá para establecer comunicación y mandar información al día acerca del comportamiento, falta de tareas, uniformes o situaciones de sus hijos en la escuela. Los padres deben acudir a la entrega de clasificaciones cada bimestre no se entregarán resultados a familiares ni hermanos mayores. Cualquier cambio de domicilio y teléfono deberá comunicarlo de inmediato a la dirección de la escuela.</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Participar y apoyar a sus hijos, en las actividades económicas (rifas, candidatas) culturales (platicas, escuela para padres).</li><li data-list="ordered" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Revisar diariamente el cuadernillo de tutoría, firmar y supervisar que cumpla con las actividades encomendadas por el maestro.</li></ol><p class="ql-align-justify"><strong>Notas:</strong></p><ol><li data-list="bullet" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Se les llamara a los padres de familia ante cualquier situación que se requiera y se firmara una carta compromiso por el alumno y sus padres. Se establecerán sanciones de acuerdo con la gravedad de la falta.</li><li data-list="bullet" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Al dejar y recoger a sus hijos, favor de respetar a los señalamientos de vitalidad, no obstruir la calle y estacionamiento de la escuela.</li><li data-list="bullet" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>Solamente el padre o tutor es el autorizado para retirar al alumno de la escuela.</li><li data-list="bullet" class="ql-align-justify"><span class="ql-ui" contenteditable="false"></span>En caso de daño a las instalaciones el alumno deberá pagar su costo.</li></ol>',
                'last_updated' => '2025-12-10 16:11:37',
            ]
        );
    }
}

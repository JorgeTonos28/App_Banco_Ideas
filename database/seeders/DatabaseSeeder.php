<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Idea;
use App\Models\IdeaComment;
use App\Models\IdeaCommentLike;
use App\Models\IdeaRating;
use App\Models\IdeaStatusHistory;
use App\Models\Regional;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 0. Create Official INFOTEP Regionals
        $regionalsData = [
            ['code' => 'ONA', 'name' => 'Oficina Nacional', 'order' => 1],
            ['code' => 'DRM', 'name' => 'Regional Metropolitana', 'order' => 2],
            ['code' => 'DRO', 'name' => 'Regional Oriental', 'order' => 3],
            ['code' => 'DRV', 'name' => 'Regional Valdesia', 'order' => 4],
            ['code' => 'DRCS', 'name' => 'Regional Cibao Sur', 'order' => 5],
            ['code' => 'DRE', 'name' => 'Regional Este', 'order' => 6],
            ['code' => 'DRCN', 'name' => 'Regional Norte', 'order' => 7],
            ['code' => 'DRCNE', 'name' => 'Regional Nordeste', 'order' => 8],
            ['code' => 'DRS', 'name' => 'Regional Sur', 'order' => 9],
        ];

        $regionals = [];
        foreach ($regionalsData as $r) {
            $regionals[$r['code']] = Regional::create($r);
        }

        // 1. Create Users
        $admin = User::create([
            'name' => 'Carlos Mendoza',
            'email' => 'admin@infotep.gob.do',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'job_title' => 'Director de Innovación y Desarrollo',
            'department' => 'Dirección de Innovación',
            'regional' => 'ONA - Oficina Nacional',
            'regional_id' => $regionals['ONA']->id,
            'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80',
            'bio' => 'Impulsando la transformación digital y metodológica en la formación técnico-profesional de la República Dominicana.',
            'is_active' => true,
        ]);

        $maria = User::create([
            'name' => 'María González',
            'email' => 'maria.gonzalez@infotep.gob.do',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'job_title' => 'Coordinadora de Formación Virtual',
            'department' => 'Formación Profesional',
            'regional' => 'DRM - Regional Metropolitana',
            'regional_id' => $regionals['DRM']->id,
            'avatar' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=200&auto=format&fit=crop&q=80',
            'bio' => 'Apasionada por la educación híbrida y el aprendizaje colaborativo.',
            'is_active' => true,
        ]);

        $luis = User::create([
            'name' => 'Luis Morales',
            'email' => 'luis.morales@infotep.gob.do',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'job_title' => 'Instructor Técnico Industrial',
            'department' => 'Talleres y Laboratorios',
            'regional' => 'DRCN - Regional Norte',
            'regional_id' => $regionals['DRCN']->id,
            'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&auto=format&fit=crop&q=80',
            'bio' => 'Comprometido con la sostenibilidad y la modernización de los talleres técnicos.',
            'is_active' => true,
        ]);

        $laura = User::create([
            'name' => 'Laura Jiménez',
            'email' => 'laura.jimenez@infotep.gob.do',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'job_title' => 'Especialista en Procesos y Logística',
            'department' => 'Operaciones',
            'regional' => 'DRE - Regional Este',
            'regional_id' => $regionals['DRE']->id,
            'avatar' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=200&auto=format&fit=crop&q=80',
            'bio' => 'Optimizando flujos operativos y experiencia de entrega de servicios.',
            'is_active' => true,
        ]);

        $francisco = User::create([
            'name' => 'Francisco Reyes',
            'email' => 'francisco.reyes@infotep.gob.do',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'job_title' => 'Docente de Tecnologías de Información',
            'department' => 'Tecnología',
            'regional' => 'DRS - Regional Sur',
            'regional_id' => $regionals['DRS']->id,
            'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=200&auto=format&fit=crop&q=80',
            'bio' => 'Promoviendo el software libre, ciberseguridad e inteligencia artificial.',
            'is_active' => true,
        ]);

        $ana = User::create([
            'name' => 'Ana Castillo',
            'email' => 'ana.castillo@infotep.gob.do',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'job_title' => 'Analista de Talento Humano',
            'department' => 'Recursos Humanos',
            'regional' => 'ONA - Oficina Nacional',
            'regional_id' => $regionals['ONA']->id,
            'avatar' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=200&auto=format&fit=crop&q=80',
            'bio' => 'Enfocada en el bienestar laboral y el desarrollo de competencias blandas.',
            'is_active' => true,
        ]);

        // 2. Create Categories
        $catProcesos = Category::create([
            'name' => 'Procesos',
            'slug' => 'procesos',
            'icon' => 'account_tree',
            'color' => '#003e6f',
            'description' => 'Mejoras en flujos de trabajo, trámites y simplificación administrativa.',
        ]);

        $catFormacion = Category::create([
            'name' => 'Formación',
            'slug' => 'formacion',
            'icon' => 'school',
            'color' => '#7c5800',
            'description' => 'Metodologías docentes, planes de estudio y capacitación técnico-profesional.',
        ]);

        $catTecnologia = Category::create([
            'name' => 'Tecnología',
            'slug' => 'tecnologia',
            'icon' => 'memory',
            'color' => '#231fb5',
            'description' => 'Soluciones digitales, automatización, IA e infraestructura tecnológica.',
        ]);

        $catParticipante = Category::create([
            'name' => 'Experiencia del Participante',
            'slug' => 'experiencia-del-participante',
            'icon' => 'group',
            'color' => '#00838f',
            'description' => 'Iniciativas dirigidas a enriquecer el aprendizaje y servicio a los estudiantes.',
        ]);

        $catColaborador = Category::create([
            'name' => 'Experiencia del Colaborador',
            'slug' => 'experiencia-del-colaborador',
            'icon' => 'sentiment_very_satisfied',
            'color' => '#d81b60',
            'description' => 'Bienestar laboral, clima organizacional y herramientas para el personal.',
        ]);

        $catSostenibilidad = Category::create([
            'name' => 'Sostenibilidad',
            'slug' => 'sostenibilidad',
            'icon' => 'eco',
            'color' => '#2e7d32',
            'description' => 'Eficiencia energética, reducción de residuos e impacto ambiental positivo.',
        ]);

        $catServicios = Category::create([
            'name' => 'Servicios',
            'slug' => 'servicios',
            'icon' => 'support_agent',
            'color' => '#e65100',
            'description' => 'Atención al usuario institucional, empresas y comunidad.',
        ]);

        $catInfraestructura = Category::create([
            'name' => 'Infraestructura',
            'slug' => 'infraestructura',
            'icon' => 'apartment',
            'color' => '#455a64',
            'description' => 'Espacios físicos, talleres, laboratorios y equipamiento.',
        ]);

        // 3. Create Tags
        $tagAuto = Tag::create(['name' => 'Automatización', 'slug' => 'automatizacion']);
        $tagSost = Tag::create(['name' => 'Sostenibilidad', 'slug' => 'sostenibilidad']);
        $tagProc = Tag::create(['name' => 'Procesos', 'slug' => 'procesos']);
        $tagIA = Tag::create(['name' => 'Inteligencia Artificial', 'slug' => 'inteligencia-artificial']);
        $tagDig = Tag::create(['name' => 'Digitalización', 'slug' => 'digitalizacion']);
        $tagAulas = Tag::create(['name' => 'Aulas Híbridas', 'slug' => 'aulas-hibridas']);
        $tagDoc = Tag::create(['name' => 'Docencia', 'slug' => 'docencia']);
        $tagTalleres = Tag::create(['name' => 'Talleres', 'slug' => 'talleres']);
        $tagBienestar = Tag::create(['name' => 'Bienestar Laboral', 'slug' => 'bienestar-laboral']);
        $tagEficiencia = Tag::create(['name' => 'Eficiencia', 'slug' => 'eficiencia']);

        // 4. Create Sample Ideas
        // Idea 1: Aulas Híbridas Móviles (María González)
        $idea1 = Idea::create([
            'user_id' => $maria->id,
            'category_id' => $catFormacion->id,
            'title' => 'Aulas Híbridas Móviles para Centros Tecnológicos',
            'summary' => 'Equipar carritos móviles con tecnología de videoconferencia para convertir cualquier aula regular en un espacio híbrido instantáneamente.',
            'description' => "Proponemos diseñar y ensamblar carritos móviles equipados con pantallas inteligentes, cámaras PTZ con seguimiento automático por voz y micrófonos omnidireccionales de alta fidelidad.\n\nEsto permitirá que cualquier taller o aula tradicional pueda transmitir clases a estudiantes remotos de otras regionales en cuestión de minutos, sin necesidad de realizar costosas remodelaciones fijas en cada salón.",
            'problem_opportunity' => 'Actualmente muchas asignaturas especializadas no cuentan con suficientes instructores en todas las regionales del país, lo que limita la oferta formativa fuera de Santo Domingo y Santiago. Equipar aulas fijas es costoso y toma meses.',
            'status' => 'en_revision',
            'visibility' => 'public',
            'access_scope' => 'profile',
            'publication_status' => 'published',
            'community_display' => 'standalone',
            'published_at' => now()->subDays(5),
            'is_featured' => true,
            'priority' => 'alta',
            'assigned_to_user_id' => $admin->id,
            'admin_observations' => 'Excelente propuesta con alto impacto pedagógico. Evaluando costo unitario de ensamblaje.',
            'next_action' => 'Cotizar equipamiento audiovisual piloto',
            'follow_up_date' => now()->addDays(7),
            'views_count' => 184,
            'created_at' => now()->subDays(5),
        ]);
        $idea1->tags()->sync([$tagAulas->id, $tagDoc->id, $tagDig->id]);

        IdeaStatusHistory::create([
            'idea_id' => $idea1->id,
            'user_id' => $maria->id,
            'old_status' => null,
            'new_status' => 'nueva',
            'comment' => 'Idea registrada y publicada para la comunidad.',
            'created_at' => now()->subDays(5),
        ]);
        IdeaStatusHistory::create([
            'idea_id' => $idea1->id,
            'user_id' => $admin->id,
            'old_status' => 'nueva',
            'new_status' => 'en_revision',
            'comment' => 'El comité de innovación está analizando la viabilidad técnica y presupuestaria.',
            'created_at' => now()->subDays(2),
        ]);

        // Idea 2: Talleres Solares (Luis Morales)
        $idea2 = Idea::create([
            'user_id' => $luis->id,
            'category_id' => $catSostenibilidad->id,
            'title' => 'Talleres Solares: Autogeneración y Práctica Técnica',
            'summary' => 'Instalar sistemas de paneles solares fotovoltaicos en los techos de los talleres para reducir consumo eléctrico y servir como laboratorio vivo.',
            'description' => 'Instalación de paneles solares fotovoltaicos en las naves industriales de los centros de formación. El sistema estará conectado a la red y a la vez funcionará como aula de práctica directa para los participantes de los cursos de Energías Renovables e Instalaciones Eléctricas Industriales.',
            'problem_opportunity' => 'Los talleres de maquinaria pesada y soldadura generan una factura eléctrica considerable. Al mismo tiempo, se requiere equipamiento moderno para capacitar en la creciente demanda de técnicos solares.',
            'status' => 'priorizada',
            'visibility' => 'public',
            'access_scope' => 'profile',
            'publication_status' => 'published',
            'community_display' => 'standalone',
            'published_at' => now()->subDays(12),
            'is_featured' => true,
            'priority' => 'estrategica',
            'assigned_to_user_id' => $admin->id,
            'admin_observations' => 'Alineado con los objetivos de desarrollo sostenible y transición energética institucional.',
            'next_action' => 'Presentar ante la Dirección General',
            'follow_up_date' => now()->addDays(14),
            'views_count' => 312,
            'created_at' => now()->subDays(12),
        ]);
        $idea2->tags()->sync([$tagSost->id, $tagTalleres->id, $tagEficiencia->id]);

        IdeaStatusHistory::create([
            'idea_id' => $idea2->id,
            'user_id' => $luis->id,
            'old_status' => null,
            'new_status' => 'nueva',
            'comment' => 'Propuesta inicial compartida.',
            'created_at' => now()->subDays(12),
        ]);
        IdeaStatusHistory::create([
            'idea_id' => $idea2->id,
            'user_id' => $admin->id,
            'old_status' => 'nueva',
            'new_status' => 'en_revision',
            'comment' => 'Revisión técnica favorable por el departamento de ingeniería.',
            'created_at' => now()->subDays(8),
        ]);
        IdeaStatusHistory::create([
            'idea_id' => $idea2->id,
            'user_id' => $admin->id,
            'old_status' => 'en_revision',
            'new_status' => 'priorizada',
            'comment' => 'Marcada como iniciativa estratégica para el plan operativo anual.',
            'created_at' => now()->subDays(3),
        ]);

        // Idea 3: Optimización de Rutas con IA (Laura Jiménez)
        $idea3 = Idea::create([
            'user_id' => $laura->id,
            'category_id' => $catTecnologia->id,
            'title' => 'Optimización de Rutas de Supervisión y Entrega con IA',
            'summary' => 'Implementar un sistema de enrutamiento dinámico con inteligencia artificial para la flota de supervisión docente y entrega de materiales.',
            'description' => 'Desarrollar o integrar un motor de ruteo inteligente que calcule en tiempo real los traslados óptimos para los supervisores técnicos y vehículos de logística institucional, considerando tráfico, horarios de talleres y prioridades de visita.',
            'problem_opportunity' => 'Actualmente las visitas a centros operativos y empresas del programa dual se planifican manualmente en hojas de cálculo, generando sobrecostos de combustible y tiempos muertos en traslados.',
            'status' => 'en_desarrollo',
            'visibility' => 'public',
            'access_scope' => 'profile',
            'publication_status' => 'published',
            'community_display' => 'standalone',
            'published_at' => now()->subDays(20),
            'is_featured' => true,
            'priority' => 'alta',
            'assigned_to_user_id' => $francisco->id,
            'admin_observations' => 'Desarrollo de prototipo en curso junto al equipo de sistemas.',
            'next_action' => 'Prueba piloto en Regional Central',
            'follow_up_date' => now()->addDays(5),
            'views_count' => 245,
            'created_at' => now()->subDays(20),
        ]);
        $idea3->tags()->sync([$tagIA->id, $tagProc->id, $tagAuto->id]);

        // Idea 4: Repositorio Digital de Manuales Técnicos (Francisco Reyes) - IMPLEMENTADA
        $idea4 = Idea::create([
            'user_id' => $francisco->id,
            'category_id' => $catTecnologia->id,
            'title' => 'Librería y Repositorio Digital Compartido de Manuales',
            'summary' => 'Repositorio institucional centralizado y accesible desde móviles con manuales técnicos actualizados, guías de taller y diagramas interactivos.',
            'description' => 'Creación de una plataforma digital donde los docentes y participantes puedan consultar y descargar todo el material técnico oficial sin depender de fotocopias o memorias USB.',
            'problem_opportunity' => 'Gran dispersión de versiones desactualizadas de manuales técnicos en fotocopias y retrasos para que los estudiantes accedan al contenido pedagógico oficial.',
            'status' => 'implementada',
            'visibility' => 'public',
            'access_scope' => 'profile',
            'publication_status' => 'published',
            'community_display' => 'standalone',
            'published_at' => now()->subDays(45),
            'is_featured' => true,
            'priority' => 'alta',
            'implemented_at' => now()->subDays(10),
            'views_count' => 480,
            'created_at' => now()->subDays(60),
        ]);
        $idea4->tags()->sync([$tagDig->id, $tagDoc->id, $tagEficiencia->id]);

        IdeaStatusHistory::create([
            'idea_id' => $idea4->id,
            'user_id' => $francisco->id,
            'old_status' => null,
            'new_status' => 'nueva',
            'comment' => 'Idea creada por Francisco Reyes.',
            'created_at' => now()->subDays(60),
        ]);
        IdeaStatusHistory::create([
            'idea_id' => $idea4->id,
            'user_id' => $admin->id,
            'old_status' => 'en_desarrollo',
            'new_status' => 'implementada',
            'comment' => '¡Iniciativa lanzada exitosamente a nivel nacional en la nube institucional!',
            'created_at' => now()->subDays(10),
        ]);

        // Idea 5: Programa de Pausas Activas y Ergonomía (Ana Castillo)
        $idea5 = Idea::create([
            'user_id' => $ana->id,
            'category_id' => $catColaborador->id,
            'title' => 'Pausas Activas Guiadas y Asesoría Ergonómica',
            'summary' => 'Micro-rutinas de estiramiento y ergonomía de 5 minutos integradas en el día a día para docentes de pie y personal administrativo.',
            'description' => 'Implementar un programa preventivo de salud ocupacional con cápsulas breves de ejercicios posturales para prevenir fatiga y lesiones por esfuerzo repetitivo en talleres y oficinas.',
            'problem_opportunity' => 'Altas quejas de dolores lumbares y fatiga muscular en instructores de talleres técnicos que pasan largas jornadas de pie.',
            'status' => 'nueva',
            'visibility' => 'public',
            'access_scope' => 'profile',
            'publication_status' => 'published',
            'community_display' => 'standalone',
            'published_at' => now()->subDay(),
            'is_featured' => false,
            'priority' => 'media',
            'views_count' => 95,
            'created_at' => now()->subDays(1),
        ]);
        $idea5->tags()->sync([$tagBienestar->id]);

        // Idea 6: Sistema Digital de Gestión de Sobrantes (Carlos Mendoza)
        $idea6 = Idea::create([
            'user_id' => $admin->id,
            'category_id' => $catProcesos->id,
            'title' => 'Plataforma de Intercambio y Reutilización de Materiales entre Talleres',
            'summary' => 'Sistema digital para registrar y transferir excedentes de materia prima (maderas, metales, componentes) entre centros de formación.',
            'description' => 'Un catálogo en línea donde cada taller registra sobrantes útiles no aprovechados en sus prácticas para que otros centros puedan solicitarlos en lugar de comprar nuevo inventario.',
            'problem_opportunity' => 'Desperdicio de retazos útiles en talleres grandes mientras talleres satélites carecen de materiales para prácticas básicas.',
            'status' => 'en_revision',
            'visibility' => 'public',
            'access_scope' => 'profile',
            'publication_status' => 'published',
            'community_display' => 'standalone',
            'published_at' => now()->subDays(4),
            'is_featured' => false,
            'priority' => 'media',
            'views_count' => 120,
            'created_at' => now()->subDays(4),
        ]);
        $idea6->tags()->sync([$tagSost->id, $tagProc->id, $tagTalleres->id]);

        // 5. Seed Ratings & Votes
        $allUsers = [$admin, $maria, $luis, $laura, $francisco, $ana];
        $allIdeas = [$idea1, $idea2, $idea3, $idea4, $idea5, $idea6];

        // Seed ratings (prevent creator from rating own idea)
        foreach ($allIdeas as $idea) {
            foreach ($allUsers as $user) {
                if ($idea->user_id !== $user->id) {
                    $randomRating = match ($idea->id) {
                        $idea1->id => rand(4, 5),
                        $idea2->id => rand(4, 5),
                        $idea3->id => rand(4, 5),
                        $idea4->id => 5,
                        $idea5->id => rand(3, 5),
                        default => rand(3, 4),
                    };

                    IdeaRating::create([
                        'idea_id' => $idea->id,
                        'user_id' => $user->id,
                        'rating' => $randomRating,
                        'created_at' => $idea->created_at->addHours(rand(1, 24)),
                    ]);
                }
            }
        }

        // 6. Seed Comments & Conversations
        $comment1 = IdeaComment::create([
            'idea_id' => $idea1->id,
            'user_id' => $luis->id,
            'content' => 'Excelente propuesta, María. En el taller de Mecánica en Santiago podríamos aprovecharlo para transmitir demostraciones de tornos CNC a las regionales del Sur.',
            'likes_count' => 3,
            'created_at' => now()->subDays(4),
        ]);
        IdeaCommentLike::create(['idea_comment_id' => $comment1->id, 'user_id' => $maria->id]);
        IdeaCommentLike::create(['idea_comment_id' => $comment1->id, 'user_id' => $admin->id]);
        IdeaCommentLike::create(['idea_comment_id' => $comment1->id, 'user_id' => $francisco->id]);

        // Reply to comment 1
        IdeaComment::create([
            'idea_id' => $idea1->id,
            'user_id' => $maria->id,
            'parent_id' => $comment1->id,
            'content' => '¡Totalmente de acuerdo, Luis! La idea es que los carritos tengan baterías de respaldo para que puedan rodar entre talleres sin desconectar la llamada.',
            'likes_count' => 2,
            'created_at' => now()->subDays(3),
        ]);

        $comment2 = IdeaComment::create([
            'idea_id' => $idea2->id,
            'user_id' => $laura->id,
            'content' => 'Además del ahorro energético, podríamos medir en tiempo real los kWh generados y proyectarlos en pantallas informativas de los centros como parte de la concientización ambiental.',
            'likes_count' => 4,
            'created_at' => now()->subDays(9),
        ]);

        IdeaComment::create([
            'idea_id' => $idea3->id,
            'user_id' => $francisco->id,
            'content' => 'Ya hemos comenzado a mapear las coordenadas de los centros y convenios empresariales. Podemos conectar el algoritmo con APIs de tráfico abierto.',
            'likes_count' => 2,
            'created_at' => now()->subDays(15),
        ]);

        // 7. Recalculate all Innovation Scores
        foreach ($allIdeas as $idea) {
            $idea->recalculateRatingAndScore();
        }
    }
}

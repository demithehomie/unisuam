<?php
/**
 * UNISUAM – config.php
 * Initialises the SQLite database, creates tables, seeds posts, and
 * exposes $db (PDO) plus jsonResponse() to every file that requires us.
 */

// ── Database path ─────────────────────────────────────────────────────────────
define('DB_DIR',  __DIR__ . '/data');
define('DB_FILE', DB_DIR  . '/unisuam.db');

if (!is_dir(DB_DIR)) {
    mkdir(DB_DIR, 0755, true);
}

// ── PDO connection ────────────────────────────────────────────────────────────
try {
    $db = new PDO('sqlite:' . DB_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('PRAGMA foreign_keys=ON');
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// ── Schema ────────────────────────────────────────────────────────────────────
$db->exec("
CREATE TABLE IF NOT EXISTS posts (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  slug       TEXT UNIQUE NOT NULL,
  title      TEXT NOT NULL,
  excerpt    TEXT,
  category   TEXT DEFAULT 'Notícias',
  content    TEXT,
  emoji      TEXT DEFAULT '📰',
  author     TEXT DEFAULT 'Redação UNISUAM',
  views      INTEGER DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS likes (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  post_id    INTEGER NOT NULL,
  ip_hash    TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(post_id, ip_hash)
);

CREATE TABLE IF NOT EXISTS comments (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  post_id    INTEGER NOT NULL,
  name       TEXT NOT NULL,
  email      TEXT,
  content    TEXT NOT NULL,
  approved   INTEGER DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS shares (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  post_id    INTEGER NOT NULL,
  platform   TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
");

// ── Seed posts ────────────────────────────────────────────────────────────────
$count = (int) $db->query('SELECT COUNT(*) FROM posts')->fetchColumn();

if ($count === 0) {
    $insert = $db->prepare("
        INSERT INTO posts (slug, title, excerpt, category, content, emoji, author, created_at)
        VALUES (:slug, :title, :excerpt, :category, :content, :emoji, :author, :created_at)
    ");

    $seeds = [
        [
            'slug'       => 'ia-laboratorio',
            'title'      => 'UNISUAM inaugura laboratório de IA com investimento de R$ 2 milhões',
            'excerpt'    => 'Novo espaço conta com GPUs de última geração e parceria com startups de deep learning para pesquisa aplicada.',
            'category'   => 'Inovação',
            'emoji'      => '🤖',
            'author'     => 'Redação UNISUAM',
            'created_at' => '2025-04-10 09:00:00',
            'content'    => '<p>A UNISUAM deu mais um passo decisivo rumo à inovação tecnológica com a inauguração do <strong>LabIA UNISUAM</strong>, o mais moderno laboratório de inteligência artificial do Rio de Janeiro. O investimento total de <strong>R$ 2 milhões</strong> contempla servidores com GPUs NVIDIA H100, estações de trabalho de alto desempenho e uma infraestrutura de rede de 100 Gbps dedicada às demandas computacionais intensivas de modelos de aprendizado profundo.</p>
<p>O laboratório foi viabilizado por meio de uma parceria estratégica com três startups brasileiras de deep learning — Axon AI, Visio Labs e NeuralBridge —, que trarão projetos reais e mentores especializados diretamente ao campus. Os alunos dos cursos de Ciência da Computação, Engenharia de Software e áreas correlatas terão acesso prioritário ao espaço, podendo desenvolver trabalhos de conclusão de curso, iniciações científicas e projetos de extensão utilizando ferramentas de ponta.</p>
<p>Segundo a reitoria, o LabIA também será aberto à comunidade por meio de cursos livres e workshops mensais voltados a profissionais da Zona Oeste do Rio que desejam se requalificar para o mercado digital. A primeira turma de certificação em Machine Learning começa em junho de 2025, com 40 vagas gratuitas destinadas a alunos de comunidades parceiras da universidade.</p>
<p>A diretora de inovação, Profa. Dra. Carla Mendes, ressaltou que o laboratório representa muito mais do que equipamentos: "Estamos construindo um ecossistema de inovação enraizado na nossa região. Queremos que os estudantes da Zona Oeste sejam protagonistas da transformação digital do Brasil, e não apenas espectadores." O LabIA já possui três projetos aprovados pela FAPERJ em andamento e deve receber visitas de delegações internacionais ainda no segundo semestre de 2025.</p>',
        ],
        [
            'slug'       => 'vestibular-2025-2',
            'title'      => 'Vestibular 2025.2 está aberto: veja bolsas e como se inscrever',
            'excerpt'    => 'Mais de 40 cursos com vagas disponíveis e descontos de até 70% para os primeiros inscritos.',
            'category'   => 'Vestibular',
            'emoji'      => '🎓',
            'author'     => 'Comunicação Institucional',
            'created_at' => '2025-04-18 10:30:00',
            'content'    => '<p>As inscrições para o <strong>Vestibular UNISUAM 2025.2</strong> estão abertas e contemplam mais de <strong>40 cursos de graduação</strong> nas áreas de Saúde, Tecnologia, Direito, Administração, Pedagogia e muito mais. As aulas do segundo semestre têm início previsto para agosto de 2025, e os primeiros inscritos contam com condições especiais que não se repetirão ao longo do processo seletivo.</p>
<p>Os candidatos que realizarem a inscrição até o dia <strong>30 de maio</strong> têm direito a bolsas de desconto de <strong>até 70%</strong> na mensalidade, válidas para todo o curso. Além disso, quem já possui nota no ENEM dos últimos três anos pode utilizar os resultados diretamente, sem precisar fazer prova, bastando anexar o boletim durante o preenchimento do formulário online disponível no portal <em>vestibular.unisuam.edu.br</em>.</p>
<p>Entre os cursos com maior demanda estão Medicina Veterinária, Engenharia Civil, Psicologia, Análise e Desenvolvimento de Sistemas e Enfermagem. Para os cursos da área de Saúde, a UNISUAM disponibiliza laboratórios modernos, hospital escola e clínicas conveniadas para estágios supervisionados desde o segundo período. Candidatos a esses cursos também podem participar de jornadas de imersão gratuitas antes da matrícula.</p>
<p>A documentação necessária para a matrícula inclui RG, CPF, histórico escolar do ensino médio e comprovante de residência. O setor de atendimento ao candidato funciona de segunda a sábado, das 8h às 20h, tanto presencialmente no campus da Barra da Tijuca quanto pelo WhatsApp oficial da universidade. Para dúvidas sobre financiamentos estudantis — FIES e PROUNI —, a equipe de apoio financeiro orienta gratuitamente todos os candidatos durante o período de inscrições.</p>',
        ],
        [
            'slug'       => 'medicina-congresso',
            'title'      => 'Alunos de Medicina conquistam 1° lugar em congresso nacional',
            'excerpt'    => 'Equipe UNISUAM venceu categoria de pesquisa clínica no Congresso Brasileiro de Inovação em Saúde.',
            'category'   => 'Vida Acadêmica',
            'emoji'      => '🩺',
            'author'     => 'Assessoria Acadêmica',
            'created_at' => '2025-03-28 14:00:00',
            'content'    => '<p>Um grupo de seis estudantes do curso de Medicina da UNISUAM trouxe para o campus o primeiro lugar na categoria <strong>Pesquisa Clínica</strong> do <em>Congresso Brasileiro de Inovação em Saúde (CBIS 2025)</em>, realizado em São Paulo no último mês de março. O trabalho, intitulado "Uso de biomarcadores salivares para detecção precoce de sepse em pacientes pediátricos", foi orientado pelo Prof. Dr. Rodrigo Faria e desenvolvido ao longo de dezoito meses no laboratório clínico do campus.</p>
<p>A pesquisa envolveu análise de amostras coletadas em parceria com o Hospital Municipal Albert Schweitzer, na Zona Oeste do Rio, e apresentou resultados que superaram os benchmarks internacionais publicados para metodologias similares. Os resultados foram aprovados por comitê de ética e já estão em processo de submissão a um periódico científico indexado na base PubMed, o que representaria uma conquista inédita para estudantes de graduação da região.</p>
<p>A equipe vencedora é composta por estudantes entre o quinto e o oitavo período, e todos eles destacaram o papel fundamental do programa de iniciação científica da UNISUAM, que oferece bolsas de pesquisa e infraestrutura de laboratório para projetos aprovados. "Nunca imaginamos competir de igual para igual com alunos de universidades federais e do sudeste mais desenvolvido. Isso mostra que nosso ambiente de pesquisa é de verdade", afirmou Ana Beatriz Lemos, líder da equipe.</p>
<p>A vitória rendeu ao grupo um convite para apresentar os resultados no <em>International Congress of Clinical Research</em>, que ocorrerá em Lisboa em outubro de 2025. A universidade custeará integralmente a viagem dos estudantes como reconhecimento pela conquista. O reitor, Prof. Dr. Marcos Guimarães, parabenizou publicamente a equipe e anunciou o lançamento de um edital especial de pesquisa clínica para o segundo semestre, com cinco novas bolsas de iniciação científica.</p>',
        ],
        [
            'slug'       => 'campus-sustentavel',
            'title'      => 'Campus sustentável: UNISUAM zera pegada de carbono até 2027',
            'excerpt'    => 'Projeto inclui painéis solares, horta orgânica e descarte inteligente de resíduos eletrônicos.',
            'category'   => 'Sustentabilidade',
            'emoji'      => '🌱',
            'author'     => 'Núcleo de Sustentabilidade',
            'created_at' => '2025-04-02 11:00:00',
            'content'    => '<p>A UNISUAM anunciou seu ambicioso <strong>Plano de Carbono Zero 2027</strong>, um conjunto de iniciativas que colocará a universidade entre as primeiras instituições de ensino superior do Brasil a zerar completamente sua pegada de carbono. O programa foi elaborado ao longo de dois anos pelo Núcleo de Sustentabilidade da universidade, em parceria com consultoras ambientais e representantes do corpo discente, e abrange desde a geração de energia até a cadeia de consumo do refeitório.</p>
<p>A primeira fase, já em execução, contempla a instalação de <strong>1.200 painéis solares fotovoltaicos</strong> nos telhados dos blocos A, B e C do campus principal. Quando concluída, a geração própria cobrirá aproximadamente 65% do consumo energético total da universidade. A energia excedente será injetada na rede da concessionária, gerando créditos que abaterão o consumo noturno e em dias de baixa irradiação solar. O investimento total dessa fase é de R$ 3,8 milhões, com retorno previsto em seis anos.</p>
<p>Além da matriz energética, o plano inclui a expansão da <strong>Horta Orgânica UNISUAM</strong> — já presente no campus desde 2022 — para uma área quatro vezes maior, abastecendo parte do refeitório com produtos cultivados pelos próprios alunos dos cursos de Nutrição e Agronomia. Pontos de coleta seletiva inteligente com sensores de peso e câmeras serão instalados em todos os andares, e um sistema de compostagem converterá resíduos orgânicos em adubo para a horta, fechando o ciclo de forma circular.</p>
<p>O descarte de resíduos eletrônicos — um dos passivos ambientais mais críticos do setor educacional — também recebe atenção especial: a UNISUAM firmou contrato com a empresa EcoTech para coleta trimestral de equipamentos obsoletos, garantindo destinação ambientalmente adequada e rastreada. Para engajar a comunidade acadêmica, o programa "UNISUAM Verde" oferecerá pontos de bonificação em descontos na mensalidade a estudantes que comprovem participação em ações sustentáveis dentro e fora do campus.</p>',
        ],
        [
            'slug'       => 'app-dengue',
            'title'      => 'Pesquisadores da UNISUAM criam app de diagnóstico precoce de dengue',
            'excerpt'    => 'Usando machine learning, o aplicativo identifica padrões com 94% de precisão já nas primeiras 24h.',
            'category'   => 'Pesquisa',
            'emoji'      => '💻',
            'author'     => 'Lab de Pesquisa Aplicada',
            'created_at' => '2025-03-15 16:45:00',
            'content'    => '<p>Uma equipe multidisciplinar de pesquisadores da UNISUAM — reunindo professores e alunos dos cursos de Medicina, Ciência da Computação e Estatística — desenvolveu o <strong>DengueAI</strong>, um aplicativo móvel capaz de identificar padrões clínicos compatíveis com dengue nas primeiras 24 horas de sintomas, com uma taxa de acurácia de <strong>94%</strong> em testes controlados. A pesquisa foi financiada por meio de edital do Ministério da Saúde e representa um avanço significativo no combate às arboviroses no Brasil.</p>
<p>O aplicativo funciona por meio de um questionário clínico estruturado, no qual o paciente ou responsável informa sintomas, histórico de febre, localização geográfica e outros dados epidemiológicos. Um modelo de machine learning treinado com mais de 120 mil casos registrados pelo SINAN (Sistema de Informação de Agravos de Notificação) analisa as respostas em tempo real e gera um score de probabilidade, orientando o usuário a buscar atendimento médico imediato quando o risco é elevado. O algoritmo foi validado em parceria com a Secretaria Municipal de Saúde do Rio de Janeiro.</p>
<p>O diferencial do DengueAI em relação a outros triadores digitais é a inclusão de dados geoespaciais em tempo real: o aplicativo cruza os sintomas do usuário com o mapa de incidência de dengue atualizado semanalmente pela Fiocruz, aumentando a sensibilidade do diagnóstico em regiões de surto ativo. Além disso, os dados anonimizados coletados pelo app retroalimentam o modelo, tornando-o progressivamente mais preciso ao longo das semanas epidemiológicas.</p>
<p>O DengueAI está disponível gratuitamente para Android e iOS desde fevereiro de 2025 e já foi baixado mais de 80 mil vezes em todo o Brasil. A equipe já recebeu manifestações de interesse de secretarias de saúde de Manaus, Fortaleza e Salvador para integrar o aplicativo às suas campanhas de vigilância epidemiológica. A próxima etapa prevê a adaptação do modelo para identificação precoce de chikungunya e Zika, aproveitando a arquitetura já desenvolvida e validada.</p>',
        ],
        [
            'slug'       => 'zona-oeste-transformacao',
            'title'      => 'Como a UNISUAM transforma a Zona Oeste do Rio de Janeiro',
            'excerpt'    => 'Mais de 60% dos nossos formandos continuam atuando na região, gerando empregos e desenvolvimento local.',
            'category'   => 'Institucional',
            'emoji'      => '🏙️',
            'author'     => 'Reitoria UNISUAM',
            'created_at' => '2025-02-20 08:00:00',
            'content'    => '<p>Fundada há mais de cinco décadas na Zona Oeste do Rio de Janeiro, a UNISUAM sempre teve como missão não apenas formar profissionais, mas transformar o território onde está inserida. Um levantamento recente do Observatório de Egressos da universidade revelou que <strong>mais de 60% dos formandos</strong> dos últimos cinco anos permanecem atuando profissionalmente na própria Zona Oeste, gerando um ciclo virtuoso de qualificação, emprego e renda na região.</p>
<p>O impacto econômico direto e indireto da UNISUAM na região é estimado em mais de <strong>R$ 400 milhões anuais</strong>, considerando salários de funcionários, fornecedores locais, movimentação do comércio do entorno e geração de empregos pelos egressos que abrem negócios na região. A universidade é um dos maiores empregadores da Zona Oeste, com aproximadamente 1.800 funcionários diretos entre docentes, técnicos e administrativos, a maioria moradora do próprio território.</p>
<p>Além do impacto econômico, a UNISUAM desenvolve mais de 30 projetos de extensão ativos que atendem gratuitamente à comunidade: clínicas de saúde, assistência jurídica popular, atendimento psicológico, cursos de informática básica e programas de alfabetização para adultos. Só no último ano, esses projetos beneficiaram diretamente mais de <strong>15 mil moradores</strong> de bairros como Bangu, Campo Grande, Realengo e Santa Cruz, regiões historicamente sub-assistidas por serviços públicos de qualidade.</p>
<p>A reitoria tem planos ambiciosos para os próximos cinco anos: expansão do campus com um novo bloco dedicado a pesquisa e inovação, abertura de mais dois polos de ensino a distância na Zona Oeste, e a criação de um fundo de bolsas sociais integrais para estudantes em situação de vulnerabilidade econômica. "A UNISUAM não existe apesar da Zona Oeste — ela existe por causa dela e para ela. Cada diploma que entregamos é um ato de desenvolvimento regional", afirmou o reitor Prof. Dr. Marcos Guimarães em recente cerimônia de formatura.</p>',
        ],
    ];

    foreach ($seeds as $post) {
        $insert->execute($post);
    }
}

// ── Helper ────────────────────────────────────────────────────────────────────
function jsonResponse(mixed $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * UNISUAM — Main Interactions
 * Nav, Theme, Course Modals, Pills, Blog
 */

// ── Theme ─────────────────────────────────────────
const html = document.documentElement;
function applyTheme(t) {
  html.dataset.theme = t;
  localStorage.setItem('uni_theme', t);
}
html.dataset.theme = localStorage.getItem('uni_theme') || 'light';

document.addEventListener('DOMContentLoaded', () => {
  // ── Theme toggle
  const tp = document.getElementById('tp');
  if (tp) tp.addEventListener('click', () => {
    applyTheme(html.dataset.theme === 'light' ? 'dark' : 'light');
  });

  // ── Hamburger / Drawer
  const ham = document.getElementById('ham');
  const drawer = document.getElementById('drawer');
  if (ham && drawer) {
    ham.addEventListener('click', () => {
      const open = ham.classList.toggle('open');
      drawer.classList.toggle('open', open);
      document.body.style.overflow = open ? 'hidden' : '';
    });
    // Close on link click
    drawer.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
      ham.classList.remove('open');
      drawer.classList.remove('open');
      document.body.style.overflow = '';
    }));
  }

  // ── Show desktop aluno btn
  if (window.innerWidth >= 960) {
    document.querySelectorAll('.nb-aluno').forEach(el => el.style.display = 'inline-flex');
  }

  // ── Pills filter
  document.querySelectorAll('.pills').forEach(pillGroup => {
    pillGroup.addEventListener('click', e => {
      const pill = e.target.closest('.pill');
      if (!pill) return;
      pillGroup.querySelectorAll('.pill').forEach(p => p.classList.remove('on'));
      pill.classList.add('on');

      const filter = pill.dataset.filter || 'all';
      const container = pillGroup.closest('[data-pill-container]') || document.querySelector('.c-grid');
      if (!container) return;

      container.querySelectorAll('[data-category]').forEach(card => {
        const match = filter === 'all' || card.dataset.category === filter;
        card.classList.toggle('hidden', !match);
        // Re-trigger reveal for newly visible cards
        if (match) {
          requestAnimationFrame(() => card.classList.add('on'));
        }
      });
    });
  });

  // ── Course Modals
  initModals();

  // ── Blog card likes (on blog-preview in index)
  initBlogLikes();

  // ── Smooth scroll for nav anchors
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
      const target = document.querySelector(a.getAttribute('href'));
      if (target) {
        e.preventDefault();
        const navH = document.querySelector('.nav')?.offsetHeight || 72;
        const top = target.getBoundingClientRect().top + window.scrollY - navH - 20;
        window.scrollTo({ top, behavior: 'smooth' });
      }
    });
  });
});

// ── Course data ───────────────────────────────────
const COURSES = [
  {
    id: 'eng-civil',
    name: 'Engenharia Civil',
    emoji: '⚡',
    cat: 'engenharia',
    modal: 'Presencial',
    duration: '10 semestres',
    price: 'R$ 1.290',
    mec: 'MEC 4',
    desc: 'Projete infraestruturas que moldam o futuro das cidades.',
    desc_long: 'Um dos cursos mais tradicionais da UNISUAM, a Engenharia Civil forma profissionais completos para atuar no planejamento, projeto e execução de obras de infraestrutura urbana, habitacional e industrial, com forte ênfase em sustentabilidade e tecnologias BIM.',
    highlights: ['Laboratório de Materiais e Solos', 'Parceria com construtoras do Rio', 'Núcleo de prática em BIM/Revit', 'Estágio obrigatório a partir do 5º semestre'],
    curriculum: [
      { sem: '1º Semestre', subjects: ['Cálculo Diferencial', 'Física Clássica I', 'Química Geral', 'Desenho Técnico', 'Comunicação Científica'] },
      { sem: '2º Semestre', subjects: ['Cálculo Integral', 'Física Clássica II', 'Topografia', 'Mecânica dos Sólidos', 'Estatística Aplicada'] },
      { sem: '3º Semestre', subjects: ['Mecânica dos Solos I', 'Resistência dos Materiais', 'Hidrologia', 'Materiais de Construção', 'AutoCAD'] },
      { sem: '4º Semestre', subjects: ['Mecânica dos Solos II', 'Estruturas de Concreto I', 'Instalações Hidráulicas', 'Geotecnia', 'Gestão de Projetos'] },
      { sem: '5º Semestre', subjects: ['Estruturas de Concreto II', 'Saneamento Básico', 'Planejamento de Obras', 'Orçamento e Controle', 'Estágio I'] },
      { sem: '6º Semestre', subjects: ['Estruturas de Aço', 'Estradas e Pavimentação', 'Instalações Elétricas', 'BIM/Revit', 'Estágio II'] },
      { sem: '7º Semestre', subjects: ['Pontes e Viadutos', 'Sustentabilidade em Obras', 'Gestão Ambiental', 'TCC I', 'Estágio III'] },
      { sem: '8º Semestre', subjects: ['Legislação e Normas ABNT', 'Empreendedorismo', 'Construção Industrializada', 'TCC II', 'Atividades Complementares'] }
    ],
    careers: ['Projetista Estrutural', 'Gestor de Obras', 'Consultor Ambiental', 'Perito Judicial', 'BIM Manager', 'Servidor Público (CREA)'],
    scholarships: [
      { name: 'FIES', icon: '🏛️', desc: 'Financiamento estudantil do Governo Federal', tag: 'Disponível' },
      { name: 'ProUni', icon: '🎓', desc: 'Bolsa integral ou parcial para renda baixa', tag: 'Até 100%' },
      { name: 'Bolsa UNISUAM', icon: '🧡', desc: 'Desconto de até 50% por desempenho no vestibular', tag: 'Mérito' },
      { name: 'PraValer', icon: '💳', desc: 'Parcelamento sem juros pelo app', tag: 'Sem juros' }
    ]
  },
  {
    id: 'medicina',
    name: 'Medicina',
    emoji: '🩺',
    cat: 'saude',
    modal: 'Presencial',
    duration: '12 semestres',
    price: 'R$ 9.800',
    mec: 'MEC 4',
    desc: 'Formação humanizada com estrutura clínica completa e hospital escola.',
    desc_long: 'O curso de Medicina da UNISUAM destaca-se pela formação humanizada e pela estrutura do CLESAM — Centro de Saúde, seu hospital-escola integrado. Os alunos têm contato com pacientes reais desde o 3º período, com supervisão de professores doutores e especialistas renomados.',
    highlights: ['Hospital escola CLESAM integrado', 'Laboratório de anatomia com 40 mesas', 'Simulação clínica com manequins de alta fidelidade', 'Intercâmbio com hospitais parceiros'],
    curriculum: [
      { sem: '1º Semestre', subjects: ['Anatomia Humana I', 'Bioquímica I', 'Biofísica', 'Histologia', 'Medicina e Sociedade'] },
      { sem: '2º Semestre', subjects: ['Anatomia Humana II', 'Bioquímica II', 'Fisiologia I', 'Imunologia', 'Saúde Coletiva I'] },
      { sem: '3º Semestre', subjects: ['Fisiologia II', 'Patologia Geral', 'Microbiologia', 'Parasitologia', 'Semiologia I'] },
      { sem: '4º Semestre', subjects: ['Patologia Especial', 'Farmacologia I', 'Semiologia II', 'Saúde Mental', 'Urgência e Emergência'] },
      { sem: '5º Semestre', subjects: ['Farmacologia II', 'Clínica Médica I', 'Pediatria I', 'Cirurgia Geral I', 'Propedêutica'] },
      { sem: '6º Semestre', subjects: ['Clínica Médica II', 'Pediatria II', 'Ginecologia e Obstetrícia I', 'Cirurgia Geral II', 'Internato I'] },
      { sem: '7º Semestre', subjects: ['Medicina de Família', 'Ginecologia II', 'Psiquiatria Clínica', 'Medicina Legal', 'Internato II'] },
      { sem: '8º Semestre', subjects: ['Internato Clínica Médica', 'Internato Cirurgia', 'Internato Pediatria', 'Internato G.O.', 'Saúde Coletiva II'] }
    ],
    careers: ['Clínico Geral', 'Especialista (via Residência)', 'Pesquisador Médico', 'Médico de Família', 'Médico do Trabalho', 'Gestor em Saúde'],
    scholarships: [
      { name: 'FIES Medicina', icon: '🏛️', desc: 'Financiamento especial para cursos de Medicina', tag: 'Disponível' },
      { name: 'Bolsa Pesquisa', icon: '🔬', desc: 'Para alunos vinculados a grupos de pesquisa', tag: 'Desempenho' },
      { name: 'Desconto CREA/CRM', icon: '👨‍⚕️', desc: 'Benefício para filhos de associados', tag: '20% off' },
      { name: 'PraValer', icon: '💳', desc: 'Parcelamento sem juros pelo aplicativo', tag: 'Sem juros' }
    ]
  },
  {
    id: 'administracao',
    name: 'Administração',
    emoji: '📈',
    cat: 'negocios',
    modal: 'EaD · Presencial',
    duration: '8 semestres',
    price: 'R$ 590',
    mec: 'MEC 4',
    desc: 'Liderança, estratégia e gestão empresarial moderna.',
    desc_long: 'O curso de Administração da UNISUAM prepara gestores com visão estratégica e competência técnica para liderar organizações em mercados cada vez mais dinâmicos. Disponível presencialmente e em EaD, com módulos internacionais opcionais e parceria com empresas da região metropolitana do Rio.',
    highlights: ['Simulador de negócios em tempo real', 'Parceria com Sebrae e Endeavor', 'Módulo de empreendedorismo e startups', 'Disponível em EaD com tutoria ao vivo'],
    curriculum: [
      { sem: '1º Semestre', subjects: ['Introdução à Administração', 'Matemática Aplicada', 'Contabilidade Geral', 'Teoria Econômica', 'Comunicação Empresarial'] },
      { sem: '2º Semestre', subjects: ['Gestão de Pessoas', 'Estatística Empresarial', 'Marketing I', 'Direito Empresarial', 'Comportamento Organizacional'] },
      { sem: '3º Semestre', subjects: ['Finanças Corporativas I', 'Marketing II', 'Gestão de Processos', 'Logística e Supply Chain', 'Pesquisa de Mercado'] },
      { sem: '4º Semestre', subjects: ['Finanças Corporativas II', 'Gestão de Projetos', 'Estratégia Empresarial', 'Análise de Investimentos', 'Gestão da Qualidade'] },
      { sem: '5º Semestre', subjects: ['Planejamento Estratégico', 'Gestão de Operações', 'Negócios Internacionais', 'Liderança e Inovação', 'Estágio I'] },
      { sem: '6º Semestre', subjects: ['Empreendedorismo', 'Gestão Ambiental', 'Business Intelligence', 'Ética nos Negócios', 'TCC I / Estágio II'] }
    ],
    careers: ['Gerente de Operações', 'Analista Financeiro', 'Consultor de Negócios', 'Empreendedor', 'Gestor de Marketing', 'Diretor Executivo'],
    scholarships: [
      { name: 'FIES', icon: '🏛️', desc: 'Financiamento estudantil do Governo Federal', tag: 'Disponível' },
      { name: 'ProUni', icon: '🎓', desc: 'Bolsa integral ou parcial para renda baixa', tag: 'Até 100%' },
      { name: 'Bolsa EaD', icon: '💻', desc: 'Desconto especial na modalidade digital', tag: '30% off' },
      { name: 'Desconto Funcionário', icon: '👔', desc: 'Para colaboradores de empresas parceiras', tag: '25% off' }
    ]
  },
  {
    id: 'direito',
    name: 'Direito',
    emoji: '⚖️',
    cat: 'direito',
    modal: 'Presencial',
    duration: '10 semestres',
    price: 'R$ 890',
    mec: 'MEC 4',
    desc: 'Formação jurídica completa com núcleo de prática profissional.',
    desc_long: 'O Direito na UNISUAM forma advogados preparados para os desafios do século XXI, com sólida base teórica e intensa prática profissional através do Escritório Modelo, onde estudantes atendem casos reais sob supervisão de professores-advogados.',
    highlights: ['Escritório Modelo com casos reais', 'Simulação de júri e audiências', 'Parceria com Tribunal de Justiça do RJ', 'Preparação intensiva para OAB'],
    curriculum: [
      { sem: '1º Semestre', subjects: ['Teoria Geral do Direito', 'Introdução à Ciência Política', 'Sociologia Jurídica', 'Metodologia Científica', 'Filosofia do Direito'] },
      { sem: '2º Semestre', subjects: ['Direito Civil I (Pessoas)', 'Direito Constitucional I', 'Direito Penal I', 'Direito Romano', 'Economia para Juristas'] },
      { sem: '3º Semestre', subjects: ['Direito Civil II (Obrigações)', 'Direito Constitucional II', 'Direito Penal II', 'Direito Administrativo I', 'Processo Civil I'] },
      { sem: '4º Semestre', subjects: ['Direito Civil III (Contratos)', 'Direito Tributário I', 'Direito Penal III', 'Processo Civil II', 'Direito do Trabalho I'] },
      { sem: '5º Semestre', subjects: ['Direito Civil IV (Família)', 'Direito Tributário II', 'Processo Penal I', 'Direito do Trabalho II', 'Processo Trabalhista'] },
      { sem: '6º Semestre', subjects: ['Direito Empresarial', 'Direito Internacional', 'Processo Penal II', 'Direito do Consumidor', 'Prática Jurídica I'] },
      { sem: '7º Semestre', subjects: ['Direito Ambiental', 'Direito Previdenciário', 'Arbitragem e Mediação', 'Prática Jurídica II', 'TCC I'] },
      { sem: '8º Semestre', subjects: ['Direito Digital', 'Compliance e LGPD', 'Prática Jurídica III', 'TCC II', 'Atividades Complementares'] }
    ],
    careers: ['Advogado(a)', 'Juiz(a) / Promotor(a)', 'Delegado(a)', 'Consultor Jurídico', 'Defensor(a) Público(a)', 'Analista Jurídico'],
    scholarships: [
      { name: 'FIES', icon: '🏛️', desc: 'Financiamento estudantil do Governo Federal', tag: 'Disponível' },
      { name: 'ProUni', icon: '🎓', desc: 'Bolsa integral ou parcial para renda baixa', tag: 'Até 100%' },
      { name: 'Bolsa OAB', icon: '⚖️', desc: 'Desconto para aprovados com nota acima de 8 no ENEM', tag: '40% off' },
      { name: 'PraValer', icon: '💳', desc: 'Parcelamento facilitado pelo app', tag: 'Sem juros' }
    ]
  },
  {
    id: 'ciencia-computacao',
    name: 'Ciência da Computação',
    emoji: '💻',
    cat: 'tecnologia',
    modal: 'EaD · Semipresencial',
    duration: '8 semestres',
    price: 'R$ 690',
    mec: 'MEC 4',
    desc: 'IA, sistemas e desenvolvimento de software de alto nível.',
    desc_long: 'O curso de Ciência da Computação da UNISUAM é referência no Rio em formação tecnológica. Com laboratórios de IA, desenvolvimento de games, segurança da informação e cloud computing, os alunos constroem portfólios reais e ingressam no mercado ainda durante a graduação.',
    highlights: ['Laboratório de IA e Machine Learning', 'Maratona de Programação (ICPC)', 'Parceria com startups da Zona Oeste', 'Projetos open source no GitHub'],
    curriculum: [
      { sem: '1º Semestre', subjects: ['Algoritmos e Lógica de Programação', 'Matemática Discreta', 'Fundamentos de TI', 'Arquitetura de Computadores', 'Inglês Técnico'] },
      { sem: '2º Semestre', subjects: ['Estruturas de Dados', 'Programação Orientada a Objetos', 'Cálculo Aplicado', 'Sistemas Operacionais', 'Banco de Dados I'] },
      { sem: '3º Semestre', subjects: ['Algoritmos Avançados', 'Desenvolvimento Web', 'Banco de Dados II', 'Redes de Computadores', 'Probabilidade e Estatística'] },
      { sem: '4º Semestre', subjects: ['Engenharia de Software', 'Desenvolvimento Mobile', 'Inteligência Artificial I', 'Segurança da Informação', 'Arquitetura de Software'] },
      { sem: '5º Semestre', subjects: ['Machine Learning', 'Cloud Computing (AWS/Azure)', 'DevOps e CI/CD', 'Computação Gráfica', 'Projeto Integrador I'] },
      { sem: '6º Semestre', subjects: ['Deep Learning', 'Blockchain e Web3', 'Gestão de Projetos Ágeis', 'UX/UI Design', 'TCC I / Estágio'] }
    ],
    careers: ['Desenvolvedor Full-Stack', 'Engenheiro de ML/IA', 'Arquiteto de Software', 'DevOps Engineer', 'Cientista de Dados', 'CTO / Tech Lead'],
    scholarships: [
      { name: 'FIES', icon: '🏛️', desc: 'Financiamento estudantil do Governo Federal', tag: 'Disponível' },
      { name: 'ProUni', icon: '🎓', desc: 'Bolsa integral ou parcial para renda baixa', tag: 'Até 100%' },
      { name: 'Bolsa Tech', icon: '💻', desc: 'Para alunos com nota > 750 no ENEM', tag: '50% off' },
      { name: 'EaD Especial', icon: '📱', desc: 'Desconto exclusivo na modalidade digital', tag: '35% off' }
    ]
  },
  {
    id: 'pedagogia',
    name: 'Pedagogia',
    emoji: '📚',
    cat: 'educacao',
    modal: 'EaD',
    duration: '8 semestres',
    price: 'R$ 390',
    mec: 'MEC 4',
    desc: 'Forme educadores de excelência para transformar vidas.',
    desc_long: 'A Pedagogia da UNISUAM forma educadores críticos, criativos e humanizados, preparados para atuar na educação básica, gestão escolar e formação corporativa. O curso tem ênfase em tecnologias educacionais e metodologias ativas, com forte conexão com escolas da rede pública e privada.',
    highlights: ['Convênio com escolas municipais e estaduais', 'Laboratório de tecnologias educacionais', 'Estágio supervisionado em escolas reais', 'Formação em metodologias ativas'],
    curriculum: [
      { sem: '1º Semestre', subjects: ['Filosofia da Educação', 'História da Educação', 'Psicologia do Desenvolvimento', 'Fundamentos da Pedagogia', 'Comunicação e Linguagem'] },
      { sem: '2º Semestre', subjects: ['Didática Geral', 'Psicologia da Aprendizagem', 'Fundamentos de Alfabetização', 'Sociologia da Educação', 'Políticas Educacionais'] },
      { sem: '3º Semestre', subjects: ['Metodologia da Alfabetização', 'Educação Inclusiva', 'Tecnologias na Educação', 'Currículo e Avaliação', 'Literatura Infantil'] },
      { sem: '4º Semestre', subjects: ['Educação Infantil', 'Arte-Educação', 'Gestão Escolar', 'Pesquisa em Educação', 'Estágio I (Infantil)'] },
      { sem: '5º Semestre', subjects: ['Ensino de Ciências', 'Matemática nos Anos Iniciais', 'Educação de Jovens e Adultos', 'Neurociência e Aprendizagem', 'Estágio II (Fundamental)'] },
      { sem: '6º Semestre', subjects: ['Educação Corporativa', 'Gestão de Projetos Pedagógicos', 'Metodologias Ativas', 'TCC I', 'Estágio III (Gestão)'] }
    ],
    careers: ['Professor(a) de Educação Básica', 'Coordenador(a) Pedagógico(a)', 'Gestor(a) Escolar', 'Analista de Treinamento', 'Especialista em EaD', 'Consultor(a) Educacional'],
    scholarships: [
      { name: 'FIES', icon: '🏛️', desc: 'Financiamento estudantil do Governo Federal', tag: 'Disponível' },
      { name: 'ProUni', icon: '🎓', desc: 'Bolsa integral ou parcial — muito disputado em Pedagogia', tag: 'Até 100%' },
      { name: 'Bolsa Professor', icon: '📚', desc: 'Para professores da rede pública em exercício', tag: '40% off' },
      { name: 'EaD Acessível', icon: '📱', desc: 'Plano especial para o curso na modalidade digital', tag: 'A partir R$ 390' }
    ]
  },
  {
    id: 'enfermagem',
    name: 'Enfermagem',
    emoji: '💊',
    cat: 'saude',
    modal: 'Presencial',
    duration: '10 semestres',
    price: 'R$ 1.090',
    mec: 'MEC 4',
    desc: 'Cuidar com excelência técnica e sensibilidade humana.',
    desc_long: 'O curso de Enfermagem da UNISUAM forma profissionais altamente qualificados para atuar em hospitais, clínicas, UBSs e na saúde domiciliar. Com laboratório de enfermagem completo e estágios no CLESAM e hospitais parceiros, os formandos têm altíssima taxa de empregabilidade.',
    highlights: ['Laboratório de técnicas de enfermagem', 'Estágio no hospital escola CLESAM', 'Parceria com hospitais da Zona Oeste', 'Preparação para COFEN e especializações'],
    curriculum: [
      { sem: '1º Semestre', subjects: ['Anatomia e Fisiologia I', 'Bioquímica Aplicada', 'Microbiologia e Parasitologia', 'Fundamentos de Enfermagem', 'Saúde Coletiva'] },
      { sem: '2º Semestre', subjects: ['Anatomia e Fisiologia II', 'Farmacologia I', 'Semiologia de Enfermagem', 'Psicologia em Saúde', 'Biossegurança'] },
      { sem: '3º Semestre', subjects: ['Farmacologia II', 'Enfermagem Médico-Cirúrgica I', 'Saúde da Mulher', 'Nutrição Clínica', 'Primeiros Socorros'] },
      { sem: '4º Semestre', subjects: ['Enfermagem Médico-Cirúrgica II', 'Enfermagem Pediátrica', 'UTI e Emergência', 'Saúde Mental', 'Estágio I'] },
      { sem: '5º Semestre', subjects: ['Enfermagem em Saúde Pública', 'Gerenciamento em Enfermagem', 'Oncologia', 'Estágio II', 'TCC I'] },
      { sem: '6º Semestre', subjects: ['Enfermagem Gerontológica', 'Auditoria em Saúde', 'Ética Profissional', 'Estágio III', 'TCC II'] }
    ],
    careers: ['Enfermeiro(a) Hospitalar', 'Enfermeiro(a) de UTI', 'Gestor(a) de Saúde', 'Enfermeiro(a) da Família', 'Professor(a) de Enfermagem', 'Consultor(a) em Saúde'],
    scholarships: [
      { name: 'FIES', icon: '🏛️', desc: 'Financiamento estudantil do Governo Federal', tag: 'Disponível' },
      { name: 'ProUni', icon: '🎓', desc: 'Bolsa integral ou parcial para renda baixa', tag: 'Até 100%' },
      { name: 'Bolsa SUS', icon: '💊', desc: 'Para profissionais da rede pública de saúde', tag: '30% off' },
      { name: 'PraValer', icon: '💳', desc: 'Parcelamento facilitado', tag: 'Sem juros' }
    ]
  },
  {
    id: 'psicologia',
    name: 'Psicologia',
    emoji: '🧠',
    cat: 'saude',
    modal: 'Presencial',
    duration: '10 semestres',
    price: 'R$ 1.190',
    mec: 'MEC 4',
    desc: 'Compreenda o comportamento humano e transforme vidas.',
    desc_long: 'A Psicologia da UNISUAM oferece formação integrada nas principais abordagens clínicas e organizacionais, com ênfase em neuropsicologia, psicologia positiva e saúde mental. O clínica escola oferece atendimentos reais supervisionados a partir do 7º período.',
    highlights: ['Clínica Escola com atendimentos reais', 'Laboratório de neuropsicologia', 'Formação em múltiplas abordagens clínicas', 'Parceria com CAPS e hospitais psiquiátricos'],
    curriculum: [
      { sem: '1º Semestre', subjects: ['História da Psicologia', 'Neurociência', 'Estatística para Psicólogos', 'Filosofia e Ciência', 'Psicologia do Desenvolvimento I'] },
      { sem: '2º Semestre', subjects: ['Teorias da Personalidade', 'Psicofisiologia', 'Psicologia Social', 'Desenvolvimento II', 'Metodologia de Pesquisa'] },
      { sem: '3º Semestre', subjects: ['Psicanálise Freudiana', 'Behaviorismo e TCC', 'Psicopatologia I', 'Avaliação Psicológica I', 'Psicologia Organizacional I'] },
      { sem: '4º Semestre', subjects: ['Psicologia Analítica (Jung)', 'Psicopatologia II', 'Avaliação Psicológica II', 'Psicologia Escolar', 'Psicologia Org. II'] },
      { sem: '5º Semestre', subjects: ['Abordagem Humanista', 'Psicologia da Saúde', 'Técnicas Psicoterápicas I', 'Neuropsicologia Clínica', 'Estágio I'] },
      { sem: '6º Semestre', subjects: ['Psicologia Forense', 'Saúde Mental Comunitária', 'Técnicas II', 'Supervisão Clínica', 'TCC I / Estágio II'] }
    ],
    careers: ['Psicólogo(a) Clínico(a)', 'Psicólogo(a) Organizacional', 'Neuropsicólogo(a)', 'Psicólogo(a) Escolar', 'Psicólogo(a) Forense', 'Pesquisador(a)'],
    scholarships: [
      { name: 'FIES', icon: '🏛️', desc: 'Financiamento estudantil do Governo Federal', tag: 'Disponível' },
      { name: 'ProUni', icon: '🎓', desc: 'Bolsa integral ou parcial para renda baixa', tag: 'Até 100%' },
      { name: 'Bolsa CRP', icon: '🧠', desc: 'Parceria com Conselho Regional de Psicologia', tag: '20% off' },
      { name: 'PraValer', icon: '💳', desc: 'Parcelamento sem juros', tag: 'Sem juros' }
    ]
  }
];

// ── Modal System ──────────────────────────────────
function initModals() {
  const overlay = document.getElementById('modal-overlay');
  const box = document.getElementById('modal-box');
  if (!overlay || !box) return;

  // Open modal on card click
  document.querySelectorAll('.c-card[data-course]').forEach(card => {
    card.addEventListener('click', () => {
      const id = card.dataset.course;
      const course = COURSES.find(c => c.id === id);
      if (course) openModal(course);
    });
    card.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        card.click();
      }
    });
    card.setAttribute('tabindex', '0');
    card.setAttribute('role', 'button');
  });

  // Close
  overlay.addEventListener('click', e => {
    if (e.target === overlay) closeModal();
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeModal();
  });
  document.getElementById('modal-close-btn')?.addEventListener('click', closeModal);
}

function openModal(course) {
  const overlay = document.getElementById('modal-overlay');
  const box = document.getElementById('modal-box');
  if (!overlay || !box) return;

  // Build modal HTML
  const thumbClass = {
    'eng-civil': 'ct-1', medicina: 'ct-2', administracao: 'ct-3',
    direito: 'ct-4', 'ciencia-computacao': 'ct-5', pedagogia: 'ct-6',
    enfermagem: 'ct-2', psicologia: 'ct-7'
  }[course.id] || 'ct-1';

  const curriculumHTML = course.curriculum.map(s => `
    <div class="sem-block">
      <div class="sem-label">${s.sem}</div>
      <ul>${s.subjects.map(sub => `<li>${sub}</li>`).join('')}</ul>
    </div>
  `).join('');

  const careersHTML = course.careers.map(c => `<div class="career-tag">${c}</div>`).join('');

  const scholsHTML = course.scholarships.map(s => `
    <div class="schol-item">
      <div class="schol-icon">${s.icon}</div>
      <div>
        <div class="schol-name">${s.name}</div>
        <div class="schol-desc">${s.desc}</div>
      </div>
      <span class="schol-badge">${s.tag}</span>
    </div>
  `).join('');

  const highlightsHTML = course.highlights.map(h => `<li>${h}</li>`).join('');

  box.innerHTML = `
    <div style="position:relative">
      <div class="modal-hero ${thumbClass}">
        <div class="hvc-mono"></div>
        <div style="position:absolute;inset:0;background:radial-gradient(circle at 70% 30%,rgba(255,77,0,.25) 0%,transparent 65%)"></div>
        <div class="modal-hero-emoji">${course.emoji}</div>
        <div class="modal-hero-badge">${course.modal}</div>
        <button class="modal-close" id="modal-close-btn" aria-label="Fechar">✕</button>
      </div>
    </div>
    <div class="modal-handle"></div>
    <div class="modal-body">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:8px">
        <h2 class="modal-title">${course.name}</h2>
        <div style="font-family:var(--fd);font-size:22px;font-weight:700;color:var(--o);white-space:nowrap">${course.price}<span style="font-family:var(--fb);font-size:11px;color:var(--t3);font-weight:400">/mês</span></div>
      </div>
      <div class="modal-meta">
        <span>⏱ ${course.duration}</span>
        <span>📍 ${course.modal}</span>
        <span>🏅 ${course.mec}</span>
      </div>
      <p style="font-size:14.5px;color:var(--t2);line-height:1.75;margin-bottom:28px;font-weight:300">${course.desc_long}</p>

      <div class="modal-tabs">
        <div class="mtab on" data-tab="grade">Grade Curricular</div>
        <div class="mtab" data-tab="carreiras">Mercado de Trabalho</div>
        <div class="mtab" data-tab="bolsas">Bolsas & Financiamento</div>
        <div class="mtab" data-tab="destaques">Destaques</div>
      </div>

      <div class="modal-panel on" data-panel="grade">
        <div class="curriculum-grid">${curriculumHTML}</div>
      </div>
      <div class="modal-panel" data-panel="carreiras">
        <h3 style="font-family:var(--fd);font-size:18px;font-weight:700;color:var(--t1);margin-bottom:16px">Onde nossos formandos atuam</h3>
        <div class="career-tags">${careersHTML}</div>
        <div style="background:var(--og);border-radius:14px;padding:20px 22px;border:1px solid rgba(255,77,0,.15);margin-top:24px">
          <div style="font-family:var(--fd);font-size:15px;font-weight:700;color:var(--o);margin-bottom:6px">📊 Empregabilidade</div>
          <div style="font-size:14px;color:var(--t2);line-height:1.65">Nossos formandos em ${course.name} têm <strong style="color:var(--t1)">87% de aproveitamento no mercado</strong> em até 6 meses após a colação de grau, segundo pesquisa de egressos 2024.</div>
        </div>
      </div>
      <div class="modal-panel" data-panel="bolsas">
        <div class="scholarship-list">${scholsHTML}</div>
        <div style="margin-top:20px;padding:18px;background:var(--bg2);border-radius:12px;border:1px solid var(--brd)">
          <div style="font-size:13px;color:var(--t3);line-height:1.65">💡 Não acumulável com outros descontos. Sujeito a disponibilidade de vagas. Entre em contato com a central de atendimento para verificar elegibilidade.</div>
        </div>
      </div>
      <div class="modal-panel" data-panel="destaques">
        <h3 style="font-family:var(--fd);font-size:18px;font-weight:700;color:var(--t1);margin-bottom:20px">Por que escolher este curso na UNISUAM?</h3>
        <ul class="crit-items" style="list-style:none;padding:0">${highlightsHTML.replace(/<li>/g, '<li class="ci"><span class="ci-icon good">✓</span>').replace(/<\/li>/g, '</li>')}</ul>
      </div>

      <div class="modal-cta">
        <a href="blog.html" class="btn btn-fill" style="font-size:14px;padding:14px 30px">Quero me inscrever agora</a>
        <button class="btn btn-ghost" style="font-size:14px;padding:14px 28px" onclick="window.open('https://api.whatsapp.com/send?phone=5521999999999&text=Ol%C3%A1%2C+tenho+interesse+no+curso+de+${encodeURIComponent(course.name)}','_blank')">💬 Falar no WhatsApp</button>
      </div>
    </div>
  `;

  // Tab logic
  box.querySelectorAll('.mtab').forEach(tab => {
    tab.addEventListener('click', () => {
      box.querySelectorAll('.mtab').forEach(t => t.classList.remove('on'));
      box.querySelectorAll('.modal-panel').forEach(p => p.classList.remove('on'));
      tab.classList.add('on');
      box.querySelector(`[data-panel="${tab.dataset.tab}"]`)?.classList.add('on');
    });
  });

  box.querySelector('#modal-close-btn')?.addEventListener('click', closeModal);

  overlay.classList.add('open');
  document.body.style.overflow = 'hidden';
  box.scrollTop = 0;
}

function closeModal() {
  const overlay = document.getElementById('modal-overlay');
  if (!overlay) return;
  overlay.classList.remove('open');
  document.body.style.overflow = '';
}

// ── Blog preview likes ────────────────────────────
function initBlogLikes() {
  document.querySelectorAll('.bc-like-btn').forEach(btn => {
    btn.addEventListener('click', async e => {
      e.stopPropagation();
      const postId = btn.dataset.postId;
      try {
        const res = await fetch(`api/likes.php?post_id=${postId}`, { method: 'POST' });
        const data = await res.json();
        btn.querySelector('.like-count').textContent = data.count;
        btn.classList.toggle('liked', data.liked);
        if (data.liked) {
          btn.style.transform = 'scale(1.35)';
          setTimeout(() => { btn.style.transform = ''; }, 300);
        }
      } catch {
        // Graceful degradation — no PHP? toggle locally
        const count = btn.querySelector('.like-count');
        const c = parseInt(count.textContent) || 0;
        const wasLiked = btn.classList.toggle('liked');
        count.textContent = wasLiked ? c + 1 : c - 1;
      }
    });
  });
}

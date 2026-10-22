<?php
/* WWAnalog — Armazenamento legado em arrays PHP; não é a base PostgreSQL. */

// ===== INÍCIO DO ARRAY PRINCIPAL =====
// Tudo que o site precisa fica dentro deste único array gigante.
// As chaves principais são:
//   - categories       → lista de categorias (slug + nome)
//   - works            → lista de obras (com seus episódios)
//   - users            → lista de usuários cadastrados
//   - comments         → comentários publicados
//   - ratings          → avaliações (estrelas)
//   - lists            → listas pessoais (já vi / estou vendo / quero ver)
//   - forum            → tópicos do fórum
//   - contacts         → mensagens de contato
//   - audit            → histórico de auditoria
//   - chat_logs        → conversas com a IA
//   - views            → registros de visualização
return array (

  // ====================================================================
  // BLOCO 1: CATEGORIAS
  // Cada categoria tem um "slug" (identificador usado em URLs e filtros)
  // e um "name" (nome legível exibido ao visitante).
  // ====================================================================
  'categories' =>
  array (
    0 => array ('slug' => 'foundfootage', 'name' => 'Found Footage'),
    1 => array ('slug' => 'bcasthijacks', 'name' => 'Broadcast Hijacks'),
    2 => array ('slug' => 'cttpsas',      'name' => 'Corporate Training Tapes / PSAs'),
    3 => array ('slug' => 'lostmedia',    'name' => 'Lost Media'),
    4 => array ('slug' => 'args',         'name' => 'ARG'),
    5 => array ('slug' => 'liminalsp',    'name' => 'Espaços Liminares'),
    6 => array ('slug' => 'mncorro',      'name' => 'Memória e Nostalgia Corrompidas'),
    7 => array ('slug' => 'animais',      'name' => 'Animais e Natureza Corrompidos'),
    8 => array ('slug' => 'jogos',        'name' => 'Jogos e Mídia Interativa Corrompidos'),
    9 => array ('slug' => 'substituicao', 'name' => 'Substituição / Doppelgängers'),
  ),

  // ====================================================================
  // BLOCO 2: OBRAS
  // Cada obra é um array com todos os seus dados + a lista de episódios.
  //
  // Estrutura de CADA OBRA:
  //   'id'             → número único
  //   'slug'           → identificador usado em URLs
  //   'title'          → título exibido
  //   'category'       → slug da categoria
  //   'tag'            → hashtag/etiqueta
  //   'description'    → sinopse curta
  //   'warning'        → aviso de conteúdo sensível
  //   'year'           → ano/período
  //   'status'         → situação (Concluída, Em andamento, etc.)
  //   'creator'        → criador
  //   'creator_url'    → link do canal do criador
  //   'image'          → caminho da capa
  //   'rating'         → classificação indicativa
  //   'original_title' → título original
  //   'synopsis'       → sinopse completa
  //   'story'          → história/enredo
  //   'tags'           → lista de tags
  //   'episodes'       → lista de episódios (cada um com number, title, url, embed_url...)
  // ====================================================================
  'works' =>
  array (

    // ------------------------------------------------------------------
    // OBRA 1: The Monument Mythos
    // Found Footage — 42 episódios cadastrados
    // ------------------------------------------------------------------
    0 =>
    array (
      'id'             => 1,
      'slug'           => 'themonumentmythos',
      'title'          => 'The Monument Mythos',
      'category'       => 'foundfootage',
      'tag'            => '#FoundFootage',
      'description'    => 'Ambientada em uma versão alternativa da história americana...',
      'warning'        => 'Conteúdo sensível: violência, morte, horror corporal...',
      'year'           => '2020–2023 (3 temporadas, 33 episódios)',
      'status'         => 'Concluída',
      'creator'        => 'Alex Casanas (Mister Manticore)',
      'image'          => '/assets/themonumentmythos.jpg',
      'rating'         => '16+',
      'original_title' => 'The Monument Mythos',
      'synopsis'       => 'Ambientada em uma versão alternativa da história americana...',
      'story'          => 'A linha do tempo principal é chamada de Deanverse...',
      'creator_url'    => '',
      'tags'           => array (0 => '#foundfootage'),

      // Lista de episódios: cada item tem:
      //   'number'    → número do episódio
      //   'title'     → título
      //   'url'       → link original (YouTube)
      //   'embed_url' → link de incorporação (YouTube no-cookie)
      //   'duration'  → duração (vazia se não informada)
      //   'description' → descrição (vazia se não informada)
      'episodes' =>
      array (
        0 => array (
          'number'    => 1,
          'title'     => 'THE MONUMENT MYTHOS | Season One Teaser',
          'url'       => 'https://www.youtube.com/watch?v=ORMFkTxmMVs&list=...',
          'embed_url' => 'https://www.youtube-nocookie.com/embed/ORMFkTxmMVs',
          'duration'  => '',
          'description' => '',
        ),
        1 => array (
          'number'    => 2,
          'title'     => 'CORNERFOLK | O Mito dos Monumentos',
          'url'       => 'https://www.youtube.com/watch?v=SzSMHFFRi8I&list=...',
          'embed_url' => 'https://www.youtube-nocookie.com/embed/SzSMHFFRi8I',
          'duration'  => '',
          'description' => '',
        ),
        // ... (os demais episódios seguem o mesmo padrão) ...
      ),
    ),

    // ------------------------------------------------------------------
    // OBRA 2: Winter of '83
    // Found Footage — curta (16 episódios)
    // ------------------------------------------------------------------
    1 =>
    array (
      'id'             => 2,
      'slug'           => 'winterof83',
      'title'          => 'Winter of \'83',
      'category'       => 'foundfootage',
      'tag'            => '#FoundFootage',
      'description'    => 'A história se passa na cidade de Fawn\'s Circle...',
      'warning'        => 'Conteúdo sensível: violência, desaparecimentos...',
      'year'           => '2023',
      'status'         => 'Concluída (curta)',
      'creator'        => 'Lewis Lovhaug (Linkara...)',
      'image'          => '/assets/winterof83.jpg',
      'rating'         => '16+',
      'original_title' => 'Winter of \'83',
      'synopsis'       => 'A história se passa na cidade de Fawn\'s Circle...',
      'story'          => 'A bactéria Lifeform foi criada em um laboratório secreto...',
      'creator_url'    => '',
      'tags'           => array (0 => '#foundfootage'),
      'episodes'       => array (
        // ... 16 episódios ...
      ),
    ),

    // ------------------------------------------------------------------
    // OBRA 3: The Backrooms (Kane Pixels)
    // Found Footage — Em andamento
    // ------------------------------------------------------------------
    2 =>
    array (
      'id'             => 3,
      'slug'           => 'backrooms',
      'title'          => 'The Backrooms (Kane Pixels)',
      'category'       => 'foundfootage',
      'tag'            => '#FoundFootage',
      'description'    => 'A série segue a descoberta de um espaço interdimensional...',
      'warning'        => 'Conteúdo sensível: suspense, perseguição...',
      'year'           => '2022–presente',
      'status'         => 'Em andamento (adaptação cinematográfica em desenvolvimento pela A24)',
      'creator'        => 'Kane Parsons (Kane Pixels)...',
      'image'          => '/assets/thebackrooms.jpg',
      'rating'         => 'TV-14 (terror psicológico, imagens perturbadoras)',
      'original_title' => 'The Backrooms (Kane Pixels)',
      'synopsis'       => 'A série segue a descoberta de um espaço interdimensional...',
      'story'          => 'No primeiro episódio, um grupo de amigos está filmando...',
      'creator_url'    => '',
      'tags'           => array (0 => '#foundfootage'),
      'episodes'       => array (
        // ... 23 episódios ...
      ),
    ),

    // ------------------------------------------------------------------
    // (As demais obras seguem exatamente o mesmo padrão acima.)
    //
    // IDs 4 a 34 incluem:
    //   - VCR Willie            (foundfootage)
    //   - No Through Road       (foundfootage)
    //   - Eventide Media Center (bcasthijacks)
    //   - Channel 7             (bcasthijacks)
    //   - Blue Channel          (bcasthijacks)
    //   - Dusk Broadcast        (bcasthijacks)
    //   - Gemini Home Entertainment (bcasthijacks)
    //   - Local 58              (bcasthijacks)
    //   - The Mandela Catalogue (bcasthijacks)
    //   - Woodlands National Park (cttpsas)
    //   - CH/SS                 (cttpsas)
    //   - White Stag Education  (cttpsas)
    //   - Lacey's Flash Games   (lostmedia)
    //   - Chimpy Chippa's       (lostmedia)
    //   - Petscop               (lostmedia)
    //   - The Walten Files      (lostmedia)
    //   - Liminal Land          (args)
    //   - Mystery Flesh Pit National Park (args)
    //   - The Complex           (liminalsp)
    //   - The Oldest View       (liminalsp)
    //   - Kraina Grzybów TV     (mncorro)
    //   - Harmony & Horror      (mncorro)
    //   - Sneaky Snack Bar      (mncorro)
    //   - Vita Carnis           (animais)
    //   - Rockwell Forest       (animais)
    //   - Super Mario 64: CLASSIFIED (jogos)
    //   - TMK / Too Much Kirby  (jogos)
    //   - Needlem0use           (jogos)
    //   - Marble Hornets        (foundfootage)
    //   - Magnitoshakhtinskaia Oblast (bcasthijacks)
    //
    // Todas com a mesma estrutura:
    //   id, slug, title, category, tag, description, warning,
    //   year, status, creator, image, rating, original_title,
    //   synopsis, story, creator_url, tags, episodes
    // ------------------------------------------------------------------

  ),

  // ====================================================================
  // BLOCO 3: USUÁRIOS
  // Cada usuário tem:
  //   'id'       → número único
  //   'name'     → nome completo
  //   'nickname' → apelido exibido
  //   'email'    → e-mail de login
  //   'password' → SENHA CRIPTOGRAFADA (hash bcrypt gerado por password_hash)
  //   'role'     → papel (Administrador, Usuário, etc.)
  //   'bio'      → descrição pessoal (opcional)
  // ====================================================================
  'users' =>
  array (
    0 =>
    array (
      'id'       => 1,
      'name'     => 'Administrador',
      'nickname' => 'Admin',
      'email'    => 'admin@wwanalog.local',
      'password' => '$2y$12$NUtl..nqb9MjK8iNqD0rkeWbAjiMzH3y0WzjLrh5nh2jejpBQTF8.',  // hash bcrypt — NUNCA a senha real
      'role'     => 'Administrador',
    ),
    1 =>
    array (
      'id'       => 2,
      'name'     => 'Desenvolvedor WWAnalog',
      'nickname' => 'Desenvolvedor',
      'email'    => 'alciiaelizabethpilarflor@gmail.com',
      'password' => '$2y$12$vBeG23co48/FQV8kDRmmXePQB9pzl7C3XAb987p.QSb0kmztSmi0y',  // hash bcrypt
      'role'     => 'Administrador',
      'bio'      => '',
    ),
  ),

  // ====================================================================
  // BLOCO 4: COLEÇÕES VAZIAS (prontas para receber dados)
  // O sistema espera que essas chaves SEMPRE existam, mesmo vazias.
  // Se não existirem, data_read() cria automaticamente.
  // ====================================================================
  'comments' => array (),   // Comentários publicados (obra ou episódio)
  'ratings'  => array (),   // Avaliações (estrelas de 1 a 5)
  'lists'    => array (),   // Listas pessoais (já vi / estou vendo / quero ver)
  'forum'    => array (),   // Tópicos do fórum
  'contacts' => array (),   // Mensagens enviadas pelo formulário de contato
  'audit'    => array (),   // Histórico de auditoria (ações do admin)
  'chat_logs'=> array (),   // Conversas com o assistente de IA

  // ====================================================================
  // BLOCO 5: VISUALIZAÇÕES
  // Registro de cada vez que alguém abriu uma obra ou lista de episódios.
  // Cada item tem:
  //   'type'    → 'work' (obra) ou 'episode_list' (lista de episódios)
  //   'work_id' → ID da obra visualizada
  //   'user_id' → ID do usuário (0 = visitante não logado)
  //   'at'      → data/hora no formato ISO 8601
  // ====================================================================
  'views' =>
  array (
    0 =>
    array (
      'type'    => 'work',
      'work_id' => 1,
      'user_id' => 0,
      'at'      => '2026-10-20T11:39:48+00:00',
    ),
  ),
);

-- WWAnalog: carga inicial não destrutiva para o esquema fornecido.
-- Execute somente depois de criar as 13 tabelas. Usa ON CONFLICT para permitir reexecução.
BEGIN;

-- Papéis usados para separar as permissões dos diferentes tipos de conta.
INSERT INTO roles (name, description) VALUES
 ('administrador','Gerencia o sistema e configurações administrativas'),
 ('moderador','Modera comentários e conteúdo conforme as permissões atribuídas'),
 ('membro','Usuário autenticado do WWAnalog')
ON CONFLICT (name) DO NOTHING;

-- Categorias principais para classificar as obras do catálogo.
INSERT INTO categories (name, slug, description) VALUES
 ('Found Footage','found-footage','Obras apresentadas como gravações encontradas.'),
 ('Broadcast Hijacks','broadcast-hijacks','Interferências e transmissões televisivas fictícias.'),
 ('Corporate Training Tapes / PSAs','corporate-training-tapes-psas','Treinamentos corporativos e anúncios de serviço público fictícios.'),
 ('Lost Media','lost-media','Obras com estética ou tema de mídia perdida.'),
 ('ARG','arg','Narrativas em formato de jogo de realidade alternativa.'),
 ('Substituição / Doppelgängers','substituicao-doppelgangers','Imitações, substituições e doppelgängers.'),
 ('Espaços Liminares','espacos-liminares','Ambientes liminares e espaços inquietantes.'),
 ('Memória e Nostalgia Corrompidas','memoria-nostalgia-corrompidas','Memória e nostalgia apresentadas de forma corrompida.'),
 ('EAS Scenarios','eas-scenarios','Cenários ficcionais de alertas de emergência.'),
 ('Animais e Natureza Corrompidos','animais-natureza-corrompidos','Representações corrompidas de animais e natureza.'),
 ('Jogos e Mídia Interativa Corrompidos','jogos-midia-interativa-corrompidos','Jogos e mídias interativas corrompidas.')
ON CONFLICT (slug) DO NOTHING;

-- Tags reutilizáveis que podem ser associadas às obras.
INSERT INTO tags (name, slug) VALUES
 ('Found Footage','found-footage'),
 ('Broadcast Hijacks','broadcast-hijacks'),
 ('Corporate Training Tapes / PSAs','corporate-training-tapes-psas'),
 ('Lost Media','lost-media'),
 ('ARG','arg')
ON CONFLICT (slug) DO NOTHING;

-- Obras iniciais: metadados desconhecidos ficam NULL em vez de serem inventados.
INSERT INTO works (title, slug, content_warnings) VALUES
 ('The Monument Mythos','the-monument-mythos',NULL),
 ('Winter of ''83','winter-of-83',NULL),
 ('Eventide Media Center','eventide-media-center',NULL),
 ('Channel 7 / Analog Archives','channel-7-analog-archives',NULL),
 ('Blue Channel (THALASIN)','blue-channel-thalasin',NULL),
 ('Dusk Broadcast','dusk-broadcast',NULL),
 ('Woodlands National Park','woodlands-national-park',NULL),
 ('Lacey''s Flash Games','laceys-flash-games','violência, sugestões de assassinato, sangue, cadáveres e desmembramento.'),
 ('Gilbert Garfield','gilbert-garfield',NULL),
 ('Chimpy Chippa''s','chimpy-chippas',NULL),
 ('Liminal Land','liminal-land',NULL),
 ('Mystery Flesh Pit National Park','mystery-flesh-pit-national-park',NULL),
 ('VCR Willie','vcr-willie',NULL)
ON CONFLICT (slug) DO NOTHING;

WITH links(title, category_slug, tag_slug) AS (VALUES
 ('The Monument Mythos','found-footage','found-footage'),
 ('Winter of ''83','found-footage','found-footage'),
 ('Eventide Media Center','broadcast-hijacks','broadcast-hijacks'),
 ('Channel 7 / Analog Archives','broadcast-hijacks','broadcast-hijacks'),
 ('Blue Channel (THALASIN)','broadcast-hijacks','broadcast-hijacks'),
 ('Dusk Broadcast','broadcast-hijacks','broadcast-hijacks'),
 ('Woodlands National Park','corporate-training-tapes-psas','corporate-training-tapes-psas'),
 ('Lacey''s Flash Games','lost-media','lost-media'),
 ('Gilbert Garfield','lost-media','lost-media'),
 ('Chimpy Chippa''s','lost-media','lost-media'),
 ('Liminal Land','arg','arg'),
 ('Mystery Flesh Pit National Park','arg','arg'),
 ('VCR Willie','found-footage','found-footage')
)
-- Relação muitos-para-muitos entre obras e categorias.
INSERT INTO work_categories (work_id, category_id)
SELECT w.id, c.id FROM links l JOIN works w ON w.title=l.title JOIN categories c ON c.slug=l.category_slug
ON CONFLICT DO NOTHING;

WITH links(title, tag_slug) AS (VALUES
 ('The Monument Mythos','found-footage'), ('Winter of ''83','found-footage'),
 ('Eventide Media Center','broadcast-hijacks'), ('Channel 7 / Analog Archives','broadcast-hijacks'),
 ('Blue Channel (THALASIN)','broadcast-hijacks'), ('Dusk Broadcast','broadcast-hijacks'),
 ('Woodlands National Park','corporate-training-tapes-psas'), ('Lacey''s Flash Games','lost-media'),
 ('Gilbert Garfield','lost-media'), ('Chimpy Chippa''s','lost-media'),
 ('Liminal Land','arg'), ('Mystery Flesh Pit National Park','arg'), ('VCR Willie','found-footage')
)
-- Relação muitos-para-muitos entre obras e tags.
INSERT INTO work_tags (work_id, tag_id)
SELECT w.id, t.id FROM links l JOIN works w ON w.title=l.title JOIN tags t ON t.slug=l.tag_slug
ON CONFLICT DO NOTHING;

COMMIT;

--
-- PostgreSQL database dump
--

\restrict uQR9gnRNCACZpmy7pC83LTFc8gX9tlmr4smWhvxMDMeiCNnrGiZ4RejBWuILg5D

-- Dumped from database version 18.4 (Ubuntu 18.4-0ubuntu0.26.04.1)
-- Dumped by pg_dump version 18.4 (Ubuntu 18.4-0ubuntu0.26.04.1)

-- Started on 2026-10-22 10:48:13 UTC

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 242 (class 1259 OID 49700)
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: wwadmin
--

CREATE TABLE public.audit_logs (
    id integer NOT NULL,
    user_id integer,
    action character varying(100) NOT NULL,
    entity_type character varying(100),
    entity_id integer,
    details jsonb,
    ip_address inet,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.audit_logs OWNER TO wwadmin;

--
-- TOC entry 241 (class 1259 OID 49699)
-- Name: audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: wwadmin
--

CREATE SEQUENCE public.audit_logs_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.audit_logs_id_seq OWNER TO wwadmin;

--
-- TOC entry 3657 (class 0 OID 0)
-- Dependencies: 241
-- Name: audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wwadmin
--

ALTER SEQUENCE public.audit_logs_id_seq OWNED BY public.audit_logs.id;


--
-- TOC entry 224 (class 1259 OID 49480)
-- Name: categories; Type: TABLE; Schema: public; Owner: wwadmin
--

CREATE TABLE public.categories (
    id integer NOT NULL,
    name character varying(150) NOT NULL,
    slug character varying(180) NOT NULL,
    description text,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.categories OWNER TO wwadmin;

--
-- TOC entry 223 (class 1259 OID 49479)
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: wwadmin
--

CREATE SEQUENCE public.categories_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.categories_id_seq OWNER TO wwadmin;

--
-- TOC entry 3658 (class 0 OID 0)
-- Dependencies: 223
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wwadmin
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- TOC entry 236 (class 1259 OID 49617)
-- Name: comments; Type: TABLE; Schema: public; Owner: wwadmin
--

CREATE TABLE public.comments (
    id integer NOT NULL,
    user_id integer NOT NULL,
    work_id integer NOT NULL,
    content text NOT NULL,
    status character varying(20) DEFAULT 'publicado'::character varying NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT comments_status_check CHECK (((status)::text = ANY ((ARRAY['publicado'::character varying, 'oculto'::character varying, 'removido'::character varying])::text[])))
);


ALTER TABLE public.comments OWNER TO wwadmin;

--
-- TOC entry 235 (class 1259 OID 49616)
-- Name: comments_id_seq; Type: SEQUENCE; Schema: public; Owner: wwadmin
--

CREATE SEQUENCE public.comments_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.comments_id_seq OWNER TO wwadmin;

--
-- TOC entry 3659 (class 0 OID 0)
-- Dependencies: 235
-- Name: comments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wwadmin
--

ALTER SEQUENCE public.comments_id_seq OWNED BY public.comments.id;


--
-- TOC entry 240 (class 1259 OID 49675)
-- Name: contact_requests; Type: TABLE; Schema: public; Owner: wwadmin
--

CREATE TABLE public.contact_requests (
    id integer NOT NULL,
    user_id integer,
    name character varying(150),
    email character varying(255),
    request_type character varying(50) NOT NULL,
    subject character varying(255) NOT NULL,
    message text NOT NULL,
    status character varying(20) DEFAULT 'pendente'::character varying NOT NULL,
    admin_notes text,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT contact_requests_status_check CHECK (((status)::text = ANY ((ARRAY['pendente'::character varying, 'em_analise'::character varying, 'resolvida'::character varying, 'rejeitada'::character varying])::text[])))
);


ALTER TABLE public.contact_requests OWNER TO wwadmin;

--
-- TOC entry 239 (class 1259 OID 49674)
-- Name: contact_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: wwadmin
--

CREATE SEQUENCE public.contact_requests_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.contact_requests_id_seq OWNER TO wwadmin;

--
-- TOC entry 3660 (class 0 OID 0)
-- Dependencies: 239
-- Name: contact_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wwadmin
--

ALTER SEQUENCE public.contact_requests_id_seq OWNED BY public.contact_requests.id;


--
-- TOC entry 232 (class 1259 OID 49566)
-- Name: episodes; Type: TABLE; Schema: public; Owner: wwadmin
--

CREATE TABLE public.episodes (
    id integer NOT NULL,
    work_id integer NOT NULL,
    title character varying(255) NOT NULL,
    episode_number integer,
    description text,
    release_date date,
    video_url text,
    source_url text,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.episodes OWNER TO wwadmin;

--
-- TOC entry 231 (class 1259 OID 49565)
-- Name: episodes_id_seq; Type: SEQUENCE; Schema: public; Owner: wwadmin
--

CREATE SEQUENCE public.episodes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.episodes_id_seq OWNER TO wwadmin;

--
-- TOC entry 3661 (class 0 OID 0)
-- Dependencies: 231
-- Name: episodes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wwadmin
--

ALTER SEQUENCE public.episodes_id_seq OWNED BY public.episodes.id;


--
-- TOC entry 234 (class 1259 OID 49589)
-- Name: ratings; Type: TABLE; Schema: public; Owner: wwadmin
--

CREATE TABLE public.ratings (
    id integer NOT NULL,
    user_id integer NOT NULL,
    work_id integer NOT NULL,
    score smallint NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT ratings_score_check CHECK (((score >= 1) AND (score <= 5)))
);


ALTER TABLE public.ratings OWNER TO wwadmin;

--
-- TOC entry 233 (class 1259 OID 49588)
-- Name: ratings_id_seq; Type: SEQUENCE; Schema: public; Owner: wwadmin
--

CREATE SEQUENCE public.ratings_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ratings_id_seq OWNER TO wwadmin;

--
-- TOC entry 3662 (class 0 OID 0)
-- Dependencies: 233
-- Name: ratings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wwadmin
--

ALTER SEQUENCE public.ratings_id_seq OWNED BY public.ratings.id;


--
-- TOC entry 220 (class 1259 OID 49437)
-- Name: roles; Type: TABLE; Schema: public; Owner: wwadmin
--

CREATE TABLE public.roles (
    id integer NOT NULL,
    name character varying(50) NOT NULL,
    description text
);


ALTER TABLE public.roles OWNER TO wwadmin;

--
-- TOC entry 219 (class 1259 OID 49436)
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: wwadmin
--

CREATE SEQUENCE public.roles_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.roles_id_seq OWNER TO wwadmin;

--
-- TOC entry 3663 (class 0 OID 0)
-- Dependencies: 219
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wwadmin
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- TOC entry 229 (class 1259 OID 49535)
-- Name: tags; Type: TABLE; Schema: public; Owner: wwadmin
--

CREATE TABLE public.tags (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    slug character varying(120) NOT NULL
);


ALTER TABLE public.tags OWNER TO wwadmin;

--
-- TOC entry 228 (class 1259 OID 49534)
-- Name: tags_id_seq; Type: SEQUENCE; Schema: public; Owner: wwadmin
--

CREATE SEQUENCE public.tags_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tags_id_seq OWNER TO wwadmin;

--
-- TOC entry 3664 (class 0 OID 0)
-- Dependencies: 228
-- Name: tags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wwadmin
--

ALTER SEQUENCE public.tags_id_seq OWNED BY public.tags.id;


--
-- TOC entry 238 (class 1259 OID 49647)
-- Name: user_lists; Type: TABLE; Schema: public; Owner: wwadmin
--

CREATE TABLE public.user_lists (
    id integer NOT NULL,
    user_id integer NOT NULL,
    work_id integer NOT NULL,
    list_status character varying(20) NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT user_lists_list_status_check CHECK (((list_status)::text = ANY ((ARRAY['minha_lista'::character varying, 'assistindo'::character varying, 'assistido'::character varying])::text[])))
);


ALTER TABLE public.user_lists OWNER TO wwadmin;

--
-- TOC entry 237 (class 1259 OID 49646)
-- Name: user_lists_id_seq; Type: SEQUENCE; Schema: public; Owner: wwadmin
--

CREATE SEQUENCE public.user_lists_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.user_lists_id_seq OWNER TO wwadmin;

--
-- TOC entry 3665 (class 0 OID 0)
-- Dependencies: 237
-- Name: user_lists_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wwadmin
--

ALTER SEQUENCE public.user_lists_id_seq OWNED BY public.user_lists.id;


--
-- TOC entry 222 (class 1259 OID 49450)
-- Name: users; Type: TABLE; Schema: public; Owner: wwadmin
--

CREATE TABLE public.users (
    id integer NOT NULL,
    name character varying(120) NOT NULL,
    nickname character varying(60) NOT NULL,
    email character varying(255) NOT NULL,
    password_hash text NOT NULL,
    avatar_url text,
    bio text,
    role_id integer,
    status character varying(20) DEFAULT 'ativo'::character varying NOT NULL,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT users_status_check CHECK (((status)::text = ANY ((ARRAY['ativo'::character varying, 'suspenso'::character varying, 'inativo'::character varying])::text[])))
);


ALTER TABLE public.users OWNER TO wwadmin;

--
-- TOC entry 221 (class 1259 OID 49449)
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: wwadmin
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO wwadmin;

--
-- TOC entry 3666 (class 0 OID 0)
-- Dependencies: 221
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wwadmin
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- TOC entry 227 (class 1259 OID 49517)
-- Name: work_categories; Type: TABLE; Schema: public; Owner: wwadmin
--

CREATE TABLE public.work_categories (
    work_id integer NOT NULL,
    category_id integer NOT NULL
);


ALTER TABLE public.work_categories OWNER TO wwadmin;

--
-- TOC entry 230 (class 1259 OID 49548)
-- Name: work_tags; Type: TABLE; Schema: public; Owner: wwadmin
--

CREATE TABLE public.work_tags (
    work_id integer NOT NULL,
    tag_id integer NOT NULL
);


ALTER TABLE public.work_tags OWNER TO wwadmin;

--
-- TOC entry 226 (class 1259 OID 49500)
-- Name: works; Type: TABLE; Schema: public; Owner: wwadmin
--

CREATE TABLE public.works (
    id integer NOT NULL,
    title character varying(255) NOT NULL,
    slug character varying(280),
    description text,
    cover_image text,
    creator character varying(255),
    release_year integer,
    status character varying(50),
    classification character varying(30),
    content_warnings text,
    source_url text,
    created_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    episode_count integer,
    CONSTRAINT works_release_year_check CHECK (((release_year IS NULL) OR ((release_year >= 1800) AND (release_year <= 2200))))
);


ALTER TABLE public.works OWNER TO wwadmin;

--
-- TOC entry 225 (class 1259 OID 49499)
-- Name: works_id_seq; Type: SEQUENCE; Schema: public; Owner: wwadmin
--

CREATE SEQUENCE public.works_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.works_id_seq OWNER TO wwadmin;

--
-- TOC entry 3667 (class 0 OID 0)
-- Dependencies: 225
-- Name: works_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: wwadmin
--

ALTER SEQUENCE public.works_id_seq OWNED BY public.works.id;


--
-- TOC entry 3411 (class 2604 OID 49703)
-- Name: audit_logs id; Type: DEFAULT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.audit_logs ALTER COLUMN id SET DEFAULT nextval('public.audit_logs_id_seq'::regclass);


--
-- TOC entry 3387 (class 2604 OID 49483)
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- TOC entry 3400 (class 2604 OID 49620)
-- Name: comments id; Type: DEFAULT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.comments ALTER COLUMN id SET DEFAULT nextval('public.comments_id_seq'::regclass);


--
-- TOC entry 3407 (class 2604 OID 49678)
-- Name: contact_requests id; Type: DEFAULT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.contact_requests ALTER COLUMN id SET DEFAULT nextval('public.contact_requests_id_seq'::regclass);


--
-- TOC entry 3394 (class 2604 OID 49569)
-- Name: episodes id; Type: DEFAULT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.episodes ALTER COLUMN id SET DEFAULT nextval('public.episodes_id_seq'::regclass);


--
-- TOC entry 3397 (class 2604 OID 49592)
-- Name: ratings id; Type: DEFAULT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.ratings ALTER COLUMN id SET DEFAULT nextval('public.ratings_id_seq'::regclass);


--
-- TOC entry 3382 (class 2604 OID 49440)
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- TOC entry 3393 (class 2604 OID 49538)
-- Name: tags id; Type: DEFAULT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.tags ALTER COLUMN id SET DEFAULT nextval('public.tags_id_seq'::regclass);


--
-- TOC entry 3404 (class 2604 OID 49650)
-- Name: user_lists id; Type: DEFAULT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.user_lists ALTER COLUMN id SET DEFAULT nextval('public.user_lists_id_seq'::regclass);


--
-- TOC entry 3383 (class 2604 OID 49453)
-- Name: users id; Type: DEFAULT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- TOC entry 3390 (class 2604 OID 49503)
-- Name: works id; Type: DEFAULT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.works ALTER COLUMN id SET DEFAULT nextval('public.works_id_seq'::regclass);


--
-- TOC entry 3651 (class 0 OID 49700)
-- Dependencies: 242
-- Data for Name: audit_logs; Type: TABLE DATA; Schema: public; Owner: wwadmin
--

COPY public.audit_logs (id, user_id, action, entity_type, entity_id, details, ip_address, created_at) FROM stdin;
\.


--
-- TOC entry 3633 (class 0 OID 49480)
-- Dependencies: 224
-- Data for Name: categories; Type: TABLE DATA; Schema: public; Owner: wwadmin
--

COPY public.categories (id, name, slug, description, created_at, updated_at) FROM stdin;
1	Found Footage	found-footage	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00
2	Broadcast Hijacks	broadcast-hijacks	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00
3	Corporate Training Tapes / PSAs	corporate-training-tapes-psas	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00
4	Lost Media	lost-media	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00
5	ARG	arg	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00
6	Substituição / Doppelgängers	substituicao-doppelgangers	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00
7	Espaços Liminares	espacos-liminares	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00
8	Memória e Nostalgia Corrompidas	memoria-nostalgia-corrompidas	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00
9	EAS Scenarios	eas-scenarios	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00
10	Animais e Natureza Corrompidos	animais-natureza-corrompidos	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00
11	Jogos e Mídia Interativa Corrompidos	jogos-midia-interativa-corrompidos	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00
\.


--
-- TOC entry 3645 (class 0 OID 49617)
-- Dependencies: 236
-- Data for Name: comments; Type: TABLE DATA; Schema: public; Owner: wwadmin
--

COPY public.comments (id, user_id, work_id, content, status, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 3649 (class 0 OID 49675)
-- Dependencies: 240
-- Data for Name: contact_requests; Type: TABLE DATA; Schema: public; Owner: wwadmin
--

COPY public.contact_requests (id, user_id, name, email, request_type, subject, message, status, admin_notes, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 3641 (class 0 OID 49566)
-- Dependencies: 232
-- Data for Name: episodes; Type: TABLE DATA; Schema: public; Owner: wwadmin
--

COPY public.episodes (id, work_id, title, episode_number, description, release_date, video_url, source_url, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 3643 (class 0 OID 49589)
-- Dependencies: 234
-- Data for Name: ratings; Type: TABLE DATA; Schema: public; Owner: wwadmin
--

COPY public.ratings (id, user_id, work_id, score, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 3629 (class 0 OID 49437)
-- Dependencies: 220
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: wwadmin
--

COPY public.roles (id, name, description) FROM stdin;
1	membro	Usuário comum cadastrado no WWAnalog.
2	administrador	Administrador responsável pela gestão da aplicação.
\.


--
-- TOC entry 3638 (class 0 OID 49535)
-- Dependencies: 229
-- Data for Name: tags; Type: TABLE DATA; Schema: public; Owner: wwadmin
--

COPY public.tags (id, name, slug) FROM stdin;
1	#FoundFootage	found-footage
2	#BroadcastHijacks	broadcast-hijacks
3	#CorporateTrainingTapesPSAs	corporate-training-tapes-psas
4	#LostMedia	lost-media
5	#ARG	arg
\.


--
-- TOC entry 3647 (class 0 OID 49647)
-- Dependencies: 238
-- Data for Name: user_lists; Type: TABLE DATA; Schema: public; Owner: wwadmin
--

COPY public.user_lists (id, user_id, work_id, list_status, created_at, updated_at) FROM stdin;
\.


--
-- TOC entry 3631 (class 0 OID 49450)
-- Dependencies: 222
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: wwadmin
--

COPY public.users (id, name, nickname, email, password_hash, avatar_url, bio, role_id, status, created_at, updated_at) FROM stdin;
2	Alicia	admin	aliciaepflor@gmail.com	$2y$12$HnahymMt9X50JTgMgnG1Ie5qp755fbVEeUW0R4vzdNK3KOQxSUHzi	\N	\N	2	ativo	2026-10-22 08:41:06.849242+00	2026-10-22 08:41:06.849242+00
\.


--
-- TOC entry 3636 (class 0 OID 49517)
-- Dependencies: 227
-- Data for Name: work_categories; Type: TABLE DATA; Schema: public; Owner: wwadmin
--

COPY public.work_categories (work_id, category_id) FROM stdin;
6	1
5	1
4	1
3	1
2	1
1	1
13	2
12	2
11	2
10	2
9	2
8	2
7	2
16	3
15	3
14	3
20	4
19	4
18	4
17	4
22	5
21	5
23	11
\.


--
-- TOC entry 3639 (class 0 OID 49548)
-- Dependencies: 230
-- Data for Name: work_tags; Type: TABLE DATA; Schema: public; Owner: wwadmin
--

COPY public.work_tags (work_id, tag_id) FROM stdin;
6	1
5	1
4	1
3	1
2	1
1	1
13	2
12	2
11	2
10	2
9	2
8	2
7	2
16	3
15	3
14	3
20	4
19	4
18	4
17	4
22	5
21	5
\.


--
-- TOC entry 3635 (class 0 OID 49500)
-- Dependencies: 226
-- Data for Name: works; Type: TABLE DATA; Schema: public; Owner: wwadmin
--

COPY public.works (id, title, slug, description, cover_image, creator, release_year, status, classification, content_warnings, source_url, created_at, updated_at, episode_count) FROM stdin;
1	The Monument Mythos	the-monument-mythos	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
2	Winter of '83	winter-of-83	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
3	VCR Willie	vcr-willie	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
4	Marble Hornets	marble-hornets	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
5	No Through Road	no-through-road	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
6	The Backrooms (Kane Pixels)	the-backrooms-kane-pixels	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
7	Eventide Media Center	eventide-media-center	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
8	Channel 7 / Analog Archives	channel-7-analog-archives	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
9	Blue Channel (THALASIN)	blue-channel-thalasin	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	9
10	Dusk Broadcast	dusk-broadcast	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
11	Local 58	local-58	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
12	Gemini Home Entertainment	gemini-home-entertainment	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
13	The Mandela Catalogue	the-mandela-catalogue	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
14	Woodlands National Park	woodlands-national-park	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
15	CH/SS	ch-ss	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
16	White Stag Education	white-stag-education	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
17	Lacey's Flash Games	laceys-flash-games	\N	\N	\N	\N	\N	\N	violência, sugestões de assassinato, sangue, cadáveres e desmembramento.	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
18	Chimpy Chippa's	chimpy-chippas	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
19	The Walten Files	the-walten-files	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
20	Petscop	petscop	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
21	Liminal Land	liminal-land	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
22	Mystery Flesh Pit National Park	mystery-flesh-pit-national-park	\N	\N	\N	\N	\N	\N	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	\N
23	Super Mario 64: CLASSIFIED	super-mario-64-classified	\N	\N	\N	\N	\N	12+	\N	\N	2026-10-22 08:14:01.46736+00	2026-10-22 08:14:01.46736+00	14
\.


--
-- TOC entry 3668 (class 0 OID 0)
-- Dependencies: 241
-- Name: audit_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wwadmin
--

SELECT pg_catalog.setval('public.audit_logs_id_seq', 1, false);


--
-- TOC entry 3669 (class 0 OID 0)
-- Dependencies: 223
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wwadmin
--

SELECT pg_catalog.setval('public.categories_id_seq', 11, true);


--
-- TOC entry 3670 (class 0 OID 0)
-- Dependencies: 235
-- Name: comments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wwadmin
--

SELECT pg_catalog.setval('public.comments_id_seq', 1, false);


--
-- TOC entry 3671 (class 0 OID 0)
-- Dependencies: 239
-- Name: contact_requests_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wwadmin
--

SELECT pg_catalog.setval('public.contact_requests_id_seq', 1, false);


--
-- TOC entry 3672 (class 0 OID 0)
-- Dependencies: 231
-- Name: episodes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wwadmin
--

SELECT pg_catalog.setval('public.episodes_id_seq', 1, false);


--
-- TOC entry 3673 (class 0 OID 0)
-- Dependencies: 233
-- Name: ratings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wwadmin
--

SELECT pg_catalog.setval('public.ratings_id_seq', 1, false);


--
-- TOC entry 3674 (class 0 OID 0)
-- Dependencies: 219
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wwadmin
--

SELECT pg_catalog.setval('public.roles_id_seq', 2, true);


--
-- TOC entry 3675 (class 0 OID 0)
-- Dependencies: 228
-- Name: tags_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wwadmin
--

SELECT pg_catalog.setval('public.tags_id_seq', 5, true);


--
-- TOC entry 3676 (class 0 OID 0)
-- Dependencies: 237
-- Name: user_lists_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wwadmin
--

SELECT pg_catalog.setval('public.user_lists_id_seq', 1, false);


--
-- TOC entry 3677 (class 0 OID 0)
-- Dependencies: 221
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wwadmin
--

SELECT pg_catalog.setval('public.users_id_seq', 2, true);


--
-- TOC entry 3678 (class 0 OID 0)
-- Dependencies: 225
-- Name: works_id_seq; Type: SEQUENCE SET; Schema: public; Owner: wwadmin
--

SELECT pg_catalog.setval('public.works_id_seq', 23, true);


--
-- TOC entry 3466 (class 2606 OID 49711)
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


--
-- TOC entry 3430 (class 2606 OID 49496)
-- Name: categories categories_name_key; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_name_key UNIQUE (name);


--
-- TOC entry 3432 (class 2606 OID 49494)
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- TOC entry 3434 (class 2606 OID 49498)
-- Name: categories categories_slug_key; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_slug_key UNIQUE (slug);


--
-- TOC entry 3458 (class 2606 OID 49635)
-- Name: comments comments_pkey; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.comments
    ADD CONSTRAINT comments_pkey PRIMARY KEY (id);


--
-- TOC entry 3464 (class 2606 OID 49693)
-- Name: contact_requests contact_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.contact_requests
    ADD CONSTRAINT contact_requests_pkey PRIMARY KEY (id);


--
-- TOC entry 3450 (class 2606 OID 49580)
-- Name: episodes episodes_pkey; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.episodes
    ADD CONSTRAINT episodes_pkey PRIMARY KEY (id);


--
-- TOC entry 3452 (class 2606 OID 49582)
-- Name: episodes episodes_work_id_episode_number_key; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.episodes
    ADD CONSTRAINT episodes_work_id_episode_number_key UNIQUE (work_id, episode_number);


--
-- TOC entry 3454 (class 2606 OID 49603)
-- Name: ratings ratings_pkey; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.ratings
    ADD CONSTRAINT ratings_pkey PRIMARY KEY (id);


--
-- TOC entry 3456 (class 2606 OID 49605)
-- Name: ratings ratings_user_id_work_id_key; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.ratings
    ADD CONSTRAINT ratings_user_id_work_id_key UNIQUE (user_id, work_id);


--
-- TOC entry 3420 (class 2606 OID 49448)
-- Name: roles roles_name_key; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_key UNIQUE (name);


--
-- TOC entry 3422 (class 2606 OID 49446)
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- TOC entry 3442 (class 2606 OID 49545)
-- Name: tags tags_name_key; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_name_key UNIQUE (name);


--
-- TOC entry 3444 (class 2606 OID 49543)
-- Name: tags tags_pkey; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_pkey PRIMARY KEY (id);


--
-- TOC entry 3446 (class 2606 OID 49547)
-- Name: tags tags_slug_key; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.tags
    ADD CONSTRAINT tags_slug_key UNIQUE (slug);


--
-- TOC entry 3460 (class 2606 OID 49661)
-- Name: user_lists user_lists_pkey; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.user_lists
    ADD CONSTRAINT user_lists_pkey PRIMARY KEY (id);


--
-- TOC entry 3462 (class 2606 OID 49663)
-- Name: user_lists user_lists_user_id_work_id_key; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.user_lists
    ADD CONSTRAINT user_lists_user_id_work_id_key UNIQUE (user_id, work_id);


--
-- TOC entry 3424 (class 2606 OID 49473)
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- TOC entry 3426 (class 2606 OID 49471)
-- Name: users users_nickname_key; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_nickname_key UNIQUE (nickname);


--
-- TOC entry 3428 (class 2606 OID 49469)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- TOC entry 3440 (class 2606 OID 49523)
-- Name: work_categories work_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.work_categories
    ADD CONSTRAINT work_categories_pkey PRIMARY KEY (work_id, category_id);


--
-- TOC entry 3448 (class 2606 OID 49554)
-- Name: work_tags work_tags_pkey; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.work_tags
    ADD CONSTRAINT work_tags_pkey PRIMARY KEY (work_id, tag_id);


--
-- TOC entry 3436 (class 2606 OID 49514)
-- Name: works works_pkey; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.works
    ADD CONSTRAINT works_pkey PRIMARY KEY (id);


--
-- TOC entry 3438 (class 2606 OID 49516)
-- Name: works works_slug_key; Type: CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.works
    ADD CONSTRAINT works_slug_key UNIQUE (slug);


--
-- TOC entry 3480 (class 2606 OID 49712)
-- Name: audit_logs audit_logs_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- TOC entry 3475 (class 2606 OID 49636)
-- Name: comments comments_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.comments
    ADD CONSTRAINT comments_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 3476 (class 2606 OID 49641)
-- Name: comments comments_work_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.comments
    ADD CONSTRAINT comments_work_id_fkey FOREIGN KEY (work_id) REFERENCES public.works(id) ON DELETE CASCADE;


--
-- TOC entry 3479 (class 2606 OID 49694)
-- Name: contact_requests contact_requests_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.contact_requests
    ADD CONSTRAINT contact_requests_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- TOC entry 3472 (class 2606 OID 49583)
-- Name: episodes episodes_work_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.episodes
    ADD CONSTRAINT episodes_work_id_fkey FOREIGN KEY (work_id) REFERENCES public.works(id) ON DELETE CASCADE;


--
-- TOC entry 3473 (class 2606 OID 49606)
-- Name: ratings ratings_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.ratings
    ADD CONSTRAINT ratings_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 3474 (class 2606 OID 49611)
-- Name: ratings ratings_work_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.ratings
    ADD CONSTRAINT ratings_work_id_fkey FOREIGN KEY (work_id) REFERENCES public.works(id) ON DELETE CASCADE;


--
-- TOC entry 3477 (class 2606 OID 49664)
-- Name: user_lists user_lists_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.user_lists
    ADD CONSTRAINT user_lists_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 3478 (class 2606 OID 49669)
-- Name: user_lists user_lists_work_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.user_lists
    ADD CONSTRAINT user_lists_work_id_fkey FOREIGN KEY (work_id) REFERENCES public.works(id) ON DELETE CASCADE;


--
-- TOC entry 3467 (class 2606 OID 49474)
-- Name: users users_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE SET NULL;


--
-- TOC entry 3468 (class 2606 OID 49529)
-- Name: work_categories work_categories_category_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.work_categories
    ADD CONSTRAINT work_categories_category_id_fkey FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE CASCADE;


--
-- TOC entry 3469 (class 2606 OID 49524)
-- Name: work_categories work_categories_work_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.work_categories
    ADD CONSTRAINT work_categories_work_id_fkey FOREIGN KEY (work_id) REFERENCES public.works(id) ON DELETE CASCADE;


--
-- TOC entry 3470 (class 2606 OID 49560)
-- Name: work_tags work_tags_tag_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.work_tags
    ADD CONSTRAINT work_tags_tag_id_fkey FOREIGN KEY (tag_id) REFERENCES public.tags(id) ON DELETE CASCADE;


--
-- TOC entry 3471 (class 2606 OID 49555)
-- Name: work_tags work_tags_work_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: wwadmin
--

ALTER TABLE ONLY public.work_tags
    ADD CONSTRAINT work_tags_work_id_fkey FOREIGN KEY (work_id) REFERENCES public.works(id) ON DELETE CASCADE;


-- Completed on 2026-10-22 10:48:17 UTC

--
-- PostgreSQL database dump complete
--

\unrestrict uQR9gnRNCACZpmy7pC83LTFc8gX9tlmr4smWhvxMDMeiCNnrGiZ4RejBWuILg5D


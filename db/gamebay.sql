-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Gép: localhost
-- Létrehozás ideje: 2025. Nov 11. 20:25
-- Kiszolgáló verziója: 10.4.28-MariaDB
-- PHP verzió: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Adatbázis: `gamebay`
--

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_item_id` bigint(20) UNSIGNED NOT NULL,
  `cart_id` bigint(20) UNSIGNED NOT NULL,
  `game_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `unit_price_at_add` decimal(10,2) NOT NULL,
  `added_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- A tábla adatainak kiíratása `cart_items`
--

INSERT INTO `cart_items` (`cart_item_id`, `cart_id`, `game_id`, `quantity`, `unit_price_at_add`, `added_at`) VALUES
(4, 3, 38, 1, 13990.00, '2025-11-11 19:03:16'),
(5, 4, 41, 1, 10990.00, '2025-11-11 19:26:25'),
(6, 4, 36, 1, 13990.00, '2025-11-11 19:35:50'),
(7, 4, 26, 1, 12990.00, '2025-11-11 19:36:02'),
(10, 5, 3, 1, 7990.00, '2025-11-11 20:07:38'),
(11, 6, 23, 1, 9990.00, '2025-11-11 20:11:15'),
(12, 7, 8, 1, 14990.00, '2025-11-11 20:19:04');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `comments`
--

CREATE TABLE `comments` (
  `comment_id` bigint(20) UNSIGNED NOT NULL,
  `post_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `body` varchar(1000) NOT NULL,
  `is_removed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `friendships`
--

CREATE TABLE `friendships` (
  `user_id_a` bigint(20) UNSIGNED NOT NULL,
  `user_id_b` bigint(20) UNSIGNED NOT NULL,
  `since` datetime NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `friendships_backup`
--

CREATE TABLE `friendships_backup` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `friend_user_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('pending','accepted','blocked') NOT NULL DEFAULT 'pending',
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `responded_at` datetime DEFAULT NULL,
  `since` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `friend_requests`
--

CREATE TABLE `friend_requests` (
  `request_id` bigint(20) UNSIGNED NOT NULL,
  `requester_id` bigint(20) UNSIGNED NOT NULL,
  `addressee_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('pending','accepted','declined','canceled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `decided_at` datetime DEFAULT NULL
) ;

--
-- A tábla adatainak kiíratása `friend_requests`
--

INSERT INTO `friend_requests` (`request_id`, `requester_id`, `addressee_id`, `status`, `created_at`, `decided_at`) VALUES
(1, 2, 1, 'accepted', '2025-11-09 16:15:42', '2025-11-09 16:16:07');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `games`
--

CREATE TABLE `games` (
  `game_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `publisher` varchar(120) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale_percent` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `sale_start` date DEFAULT NULL,
  `sale_end` date DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- A tábla adatainak kiíratása `games`
--

INSERT INTO `games` (`game_id`, `title`, `description`, `publisher`, `price`, `sale_percent`, `sale_start`, `sale_end`, `image_url`, `release_date`, `is_published`, `created_at`, `updated_at`) VALUES
(1, 'Cyberpunk', 'Nyílt világú sci-fi RPG.', 'CD Projekt', 11990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Cyberpunk', '2020-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(2, 'Skyrim', 'Fantasy RPG hatalmas világban.', 'Bethesda', 9990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Skyrim', '2011-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(3, 'World of Warcraft', 'Legendás MMORPG epikus kalandokkal.', 'Blizzard', 7990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=World+of+Warcraft', '2004-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(4, 'Diablo', 'Sötét akció-RPG dungeon crawler.', 'Blizzard', 8490.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Diablo', '1996-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(5, 'Half-Life', 'Ikonikus sci-fi FPS izgalmas küldetésekkel.', 'Valve', 6990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Half-Life', '1998-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(6, 'Need for Speed', 'Izgalmas autóverseny arcade stílusban.', 'Electronic Arts', 7490.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Need+for+Speed', '1994-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(7, 'God of War', 'Mitológiai hack & slash akciójáték.', 'Sony', 10990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=God+of+War', '2005-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(8, 'The Witcher 3', 'Nyílt világú RPG gazdag történettel.', 'CD Projekt', 14990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=The+Witcher+3', '2015-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(9, 'Assassins Creed Valhalla', 'Akció-kaland nyílt világban vikingekkel.', 'Ubisoft', 11990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Assassins+Creed+Valhalla', '2020-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(10, 'Horizon Zero Dawn', 'Futurisztikus nyílt világú akció-RPG.', 'Guerrilla Games', 10990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Horizon+Zero+Dawn', '2017-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(11, 'Cyberpunk 2077', 'Sci-fi RPG futurisztikus városban.', 'CD Projekt', 11990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Cyberpunk+2077', '2020-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(12, 'The Last of Us Part II', 'Túlélő akció kaland.', 'Naughty Dog', 14990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=The+Last+of+Us+II', '2020-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(13, 'Red Dead Redemption 2', 'Nyílt világú western kaland.', 'Rockstar Games', 15990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Red+Dead+Redemption+2', '2018-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(14, 'Call of Duty: Modern Warfare', 'FPS intenzív háborús kampánnyal.', 'Activision', 12990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=COD+MW', '2019-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(15, 'Overwatch', 'Csapat alapú FPS izgalmas hősökkel.', 'Blizzard', 8990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Overwatch', '2016-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(16, 'Fortnite', 'Battle Royale multiplayer akció játék.', 'Epic Games', 0.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Fortnite', '2017-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(17, 'League of Legends', 'MOBA, stratégiai csapatjáték.', 'Riot Games', 0.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=League+of+Legends', '2009-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(18, 'Minecraft', 'Sandbox kreatív és túlélő játék.', 'Mojang', 6990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Minecraft', '2011-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(19, 'Valorant', 'Taktikai FPS, hős alapú képességekkel.', 'Riot Games', 0.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Valorant', '2020-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(20, 'Resident Evil Village', 'Horror túlélő akció FPS és kaland.', 'Capcom', 11990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Resident+Evil+Village', '2021-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(21, 'Dark Souls III', 'Kihívást jelentő akció RPG.', 'FromSoftware', 12990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Dark+Souls+III', '2016-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(22, 'Elden Ring', 'Nyílt világú akció RPG.', 'FromSoftware', 15990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Elden+Ring', '2022-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(23, 'The Elder Scrolls Online', 'MMORPG Tamriel világában.', 'Zenimax', 9990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=ESO', '2014-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(24, 'GTA V', 'Nyílt világú bűnügyi akció játék.', 'Rockstar Games', 12990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=GTA+V', '2013-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(25, 'Far Cry 6', 'Nyílt világú FPS kaland.', 'Ubisoft', 11990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Far+Cry+6', '2021-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(26, 'Battlefield 2042', 'FPS nagy háborús multiplayer.', 'Electronic Arts', 12990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Battlefield+2042', '2021-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(27, 'Spider-Man: Miles Morales', 'Akció kaland szuperhős történettel.', 'Sony', 10990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Spider-Man+Miles+Morales', '2020-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(28, 'Star Wars Jedi: Fallen Order', 'Akció kaland a Star Wars univerzumban.', 'Electronic Arts', 9990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Jedi+Fallen+Order', '2019-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(29, 'Monster Hunter: World', 'Akció RPG vad szörnyekkel.', 'Capcom', 10990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Monster+Hunter+World', '2018-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(30, 'Apex Legends', 'Battle Royale hős alapú multiplayer.', 'Electronic Arts', 0.00, 20, '2025-11-11', '2025-11-30', 'https://via.placeholder.com/400x200.png?text=Apex+Legends', '2019-01-01', 1, '2025-11-07 09:48:10', '2025-11-11 18:07:04'),
(31, 'Ghost of Tsushima', 'Nyílt világú szamuráj akció kaland.', 'Sony', 11990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Ghost+of+Tsushima', '2020-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(32, 'Fall Guys', 'Party battle royale szórakoztató akadályokkal.', 'Mediatonic', 0.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Fall+Guys', '2020-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(33, 'Cyber Hunter', 'Futurisztikus battle royale kaland.', 'NetEase', 7990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Cyber+Hunter', '2019-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(34, 'Forza Horizon 5', 'Nyílt világú autóverseny.', 'Microsoft', 12990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Forza+Horizon+5', '2021-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(35, 'Kingdom Hearts III', 'Akció-RPG Disney és Square Enix karakterekkel.', 'Square Enix', 10990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Kingdom+Hearts+III', '2019-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(36, 'Death Stranding', 'Futurisztikus akció kaland.', 'Kojima Productions', 13990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Death+Stranding', '2019-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(37, 'Sekiro: Shadows Die Twice', 'Akció kaland japán szamuráj témával.', 'FromSoftware', 12990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Sekiro+Shadows+Die+Twice', '2019-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(38, 'Bloodborne', 'Sötét akció RPG horror elemekkel.', 'FromSoftware', 13990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Bloodborne', '2015-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(39, 'Demons Souls', 'Kihívást jelentő akció RPG.', 'FromSoftware', 14990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Demons+Souls', '2020-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(40, 'Dark Souls II', 'Kihívást jelentő akció RPG.', 'FromSoftware', 11990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Dark+Souls+II', '2014-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(41, 'Dark Souls', 'Kihívást jelentő akció RPG.', 'FromSoftware', 10990.00, 0, NULL, NULL, 'https://via.placeholder.com/400x200.png?text=Dark+Souls', '2011-01-01', 1, '2025-11-07 09:48:10', '2025-11-07 09:48:10'),
(42, 'test', 'nincs', 'tesdev', -0.01, 0, NULL, NULL, NULL, '2025-01-01', 1, '2025-11-11 16:00:52', '2025-11-11 16:00:52'),
(43, 'testDate', 'nincs', 'bethesda', -0.01, 0, NULL, NULL, NULL, '2030-01-01', 1, '2025-11-11 16:01:57', '2025-11-11 16:01:57'),
(44, 'testDate2', NULL, 'bethesda', 1.00, 0, NULL, NULL, NULL, '2027-07-12', 0, '2025-11-11 17:48:06', '2025-11-11 17:48:06');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `game_admin_actions`
--

CREATE TABLE `game_admin_actions` (
  `action_id` bigint(20) UNSIGNED NOT NULL,
  `admin_user_id` bigint(20) UNSIGNED NOT NULL,
  `game_id` bigint(20) UNSIGNED NOT NULL,
  `action_type` enum('create','update','delete','publish','unpublish') NOT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `game_genres`
--

CREATE TABLE `game_genres` (
  `game_id` bigint(20) UNSIGNED NOT NULL,
  `genre_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- A tábla adatainak kiíratása `game_genres`
--

INSERT INTO `game_genres` (`game_id`, `genre_id`) VALUES
(1, 1),
(2, 1),
(3, 5),
(4, 2),
(5, 4),
(6, 8),
(7, 3),
(8, 1),
(9, 3),
(10, 2),
(11, 1),
(12, 3),
(13, 3),
(14, 4),
(15, 4),
(16, 7),
(17, 6),
(18, 9),
(19, 4),
(20, 10),
(21, 2),
(22, 2),
(23, 5),
(24, 3),
(25, 4),
(26, 4),
(27, 3),
(28, 3),
(29, 2),
(30, 7),
(31, 3),
(32, 11),
(33, 7),
(34, 8),
(35, 2),
(36, 3),
(37, 2),
(38, 2),
(39, 2),
(40, 2),
(41, 2),
(42, 2),
(43, 2),
(44, 2),
(44, 3),
(44, 7);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `genres`
--

CREATE TABLE `genres` (
  `genre_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- A tábla adatainak kiíratása `genres`
--

INSERT INTO `genres` (`genre_id`, `name`) VALUES
(2, 'Action RPG'),
(3, 'Action-Adventure'),
(7, 'Battle Royale'),
(4, 'FPS'),
(10, 'Horror'),
(5, 'MMORPG'),
(6, 'MOBA'),
(11, 'Party'),
(8, 'Racing'),
(1, 'RPG'),
(9, 'Sandbox');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `libraries`
--

CREATE TABLE `libraries` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `game_id` bigint(20) UNSIGNED NOT NULL,
  `acquired_at` datetime NOT NULL DEFAULT current_timestamp(),
  `source` enum('purchase','gift','admin_grant') NOT NULL DEFAULT 'purchase',
  `total_play_seconds` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- A tábla adatainak kiíratása `libraries`
--

INSERT INTO `libraries` (`user_id`, `game_id`, `acquired_at`, `source`, `total_play_seconds`) VALUES
(1, 2, '2025-11-09 02:14:08', 'purchase', 0),
(1, 3, '2025-11-11 20:07:44', 'purchase', 0),
(1, 8, '2025-11-11 20:19:09', 'purchase', 0),
(1, 15, '2025-11-09 02:14:08', 'purchase', 24),
(1, 23, '2025-11-11 20:11:19', 'purchase', 0),
(1, 26, '2025-11-11 19:55:33', 'purchase', 0),
(1, 30, '2025-11-09 12:00:17', 'purchase', 0),
(1, 36, '2025-11-11 19:55:33', 'purchase', 0),
(1, 38, '2025-11-11 19:21:20', 'purchase', 0),
(1, 41, '2025-11-11 19:55:33', 'purchase', 0),
(2, 9, '2025-11-11 15:43:39', 'purchase', 0),
(2, 11, '2025-11-11 15:43:39', 'purchase', 0);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `likes`
--

CREATE TABLE `likes` (
  `post_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `liked_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `moderation_actions`
--

CREATE TABLE `moderation_actions` (
  `action_id` bigint(20) UNSIGNED NOT NULL,
  `admin_user_id` bigint(20) UNSIGNED NOT NULL,
  `target_post_id` bigint(20) UNSIGNED DEFAULT NULL,
  `target_comment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `target_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action_type` enum('remove_post','remove_comment','suspend_user','unsuspend_user') NOT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `orders`
--

CREATE TABLE `orders` (
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('pending','paid','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `placed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `payment_method` enum('card','paypal','other') DEFAULT 'card',
  `bill_full_name` varchar(100) DEFAULT NULL,
  `bill_address1` varchar(150) DEFAULT NULL,
  `bill_address2` varchar(150) DEFAULT NULL,
  `bill_city` varchar(100) DEFAULT NULL,
  `bill_postal_code` varchar(20) DEFAULT NULL,
  `bill_country` char(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- A tábla adatainak kiíratása `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `status`, `total_amount`, `placed_at`, `payment_method`, `bill_full_name`, `bill_address1`, `bill_address2`, `bill_city`, `bill_postal_code`, `bill_country`) VALUES
(1, 1, 'paid', 0.00, '2025-11-09 12:00:17', 'other', 'John Test', 'John Test u.', '42.', 'Budapest', '1234', 'HU'),
(2, 2, 'paid', 23980.00, '2025-11-11 15:43:38', 'other', 'Kiss Tamás Ferenc', 'Petőfi Sándor u.', '63', 'Pálmonostora', '6112', 'HU'),
(3, 1, 'paid', 13990.00, '2025-11-11 19:21:20', 'card', NULL, NULL, NULL, NULL, NULL, NULL),
(4, 1, 'paid', 37970.00, '2025-11-11 19:55:33', 'card', NULL, NULL, NULL, NULL, NULL, NULL),
(5, 1, 'paid', 7990.00, '2025-11-11 20:07:44', 'card', NULL, NULL, NULL, NULL, NULL, NULL),
(6, 1, 'paid', 9990.00, '2025-11-11 20:11:19', 'card', NULL, NULL, NULL, NULL, NULL, NULL),
(7, 1, 'paid', 14990.00, '2025-11-11 20:19:09', 'card', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `game_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- A tábla adatainak kiíratása `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `game_id`, `quantity`, `unit_price`) VALUES
(1, 1, 30, 2, 0.00),
(2, 2, 9, 1, 11990.00),
(3, 2, 11, 1, 11990.00),
(4, 3, 38, 1, 13990.00),
(5, 4, 26, 1, 12990.00),
(6, 4, 41, 1, 10990.00),
(7, 4, 36, 1, 13990.00),
(8, 5, 3, 1, 7990.00),
(9, 6, 23, 1, 9990.00),
(10, 7, 8, 1, 14990.00);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `password_resets`
--

CREATE TABLE `password_resets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `play_sessions`
--

CREATE TABLE `play_sessions` (
  `session_id` int(10) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `game_id` bigint(20) UNSIGNED NOT NULL,
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `duration_seconds` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- A tábla adatainak kiíratása `play_sessions`
--

INSERT INTO `play_sessions` (`session_id`, `user_id`, `game_id`, `started_at`, `ended_at`, `duration_seconds`) VALUES
(1, 1, 15, '2025-11-11 18:53:53', '2025-11-11 18:53:58', 5),
(2, 1, 15, '2025-11-11 18:54:01', '2025-11-11 18:54:08', 7),
(3, 1, 15, '2025-11-11 18:58:02', '2025-11-11 18:58:14', 12);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `posts`
--

CREATE TABLE `posts` (
  `post_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `game_id` bigint(20) UNSIGNED NOT NULL,
  `caption` varchar(500) DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `hidden_reason` varchar(255) DEFAULT NULL,
  `moderated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `visibility` enum('public','friends','private') NOT NULL DEFAULT 'public',
  `is_removed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- A tábla adatainak kiíratása `posts`
--

INSERT INTO `posts` (`post_id`, `user_id`, `game_id`, `caption`, `image_path`, `is_hidden`, `hidden_reason`, `moderated_by`, `visibility`, `is_removed`, `created_at`, `updated_at`) VALUES
(1, 1, 3, NULL, '/uploads/2025/11/8b03d6b723eec542a44565f976f7a652.webp', 0, NULL, NULL, 'public', 0, '2025-11-09 13:56:40', '2025-11-09 13:56:40');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `post_comments`
--

CREATE TABLE `post_comments` (
  `comment_id` bigint(20) UNSIGNED NOT NULL,
  `post_id` bigint(20) UNSIGNED NOT NULL,
  `parent_comment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `body` varchar(1000) NOT NULL,
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- A tábla adatainak kiíratása `post_comments`
--

INSERT INTO `post_comments` (`comment_id`, `post_id`, `parent_comment_id`, `user_id`, `body`, `is_hidden`, `created_at`) VALUES
(1, 1, NULL, 1, 'Oh shit that\'s deep :|', 0, '2025-11-09 14:01:28'),
(2, 1, NULL, 1, 'Yeah', 0, '2025-11-09 15:20:25'),
(3, 1, NULL, 1, 'NO :(', 0, '2025-11-09 15:20:39'),
(4, 1, 1, 1, 'Word :/', 0, '2025-11-09 15:34:11'),
(5, 1, 1, 2, 'NOICE :D', 0, '2025-11-09 16:15:30'),
(6, 1, NULL, 2, 'test2 a nevem :(', 0, '2025-11-11 15:48:29'),
(7, 1, 6, 2, 'üdv', 0, '2025-11-11 15:49:41');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `post_likes`
--

CREATE TABLE `post_likes` (
  `post_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- A tábla adatainak kiíratása `post_likes`
--

INSERT INTO `post_likes` (`post_id`, `user_id`, `created_at`) VALUES
(1, 1, '2025-11-09 14:00:56');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `roles`
--

CREATE TABLE `roles` (
  `role_id` tinyint(3) UNSIGNED NOT NULL,
  `name` enum('admin','user') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `shopping_carts`
--

CREATE TABLE `shopping_carts` (
  `cart_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('active','abandoned','converted') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- A tábla adatainak kiíratása `shopping_carts`
--

INSERT INTO `shopping_carts` (`cart_id`, `user_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'converted', '2025-11-09 11:55:39', '2025-11-09 12:00:17'),
(2, 2, 'converted', '2025-11-11 15:42:40', '2025-11-11 15:43:39'),
(3, 1, 'converted', '2025-11-11 19:03:16', '2025-11-11 19:21:20'),
(4, 1, 'converted', '2025-11-11 19:21:20', '2025-11-11 19:55:33'),
(5, 1, 'converted', '2025-11-11 19:55:36', '2025-11-11 20:07:44'),
(6, 1, 'converted', '2025-11-11 20:07:53', '2025-11-11 20:11:19'),
(7, 1, 'converted', '2025-11-11 20:12:25', '2025-11-11 20:19:09'),
(8, 1, 'active', '2025-11-11 20:19:09', '2025-11-11 20:19:09');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `users`
--

CREATE TABLE `users` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','suspended','deleted') NOT NULL DEFAULT 'active',
  `banned_until` datetime DEFAULT NULL,
  `ban_reason` varchar(255) DEFAULT NULL,
  `banned_by` bigint(20) UNSIGNED DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `billing_full_name` varchar(100) DEFAULT NULL,
  `billing_address1` varchar(150) DEFAULT NULL,
  `billing_address2` varchar(150) DEFAULT NULL,
  `billing_city` varchar(100) DEFAULT NULL,
  `billing_postal_code` varchar(20) DEFAULT NULL,
  `billing_country` char(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- A tábla adatainak kiíratása `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `status`, `banned_until`, `ban_reason`, `banned_by`, `is_admin`, `created_at`, `billing_full_name`, `billing_address1`, `billing_address2`, `billing_city`, `billing_postal_code`, `billing_country`) VALUES
(1, 'test1', 'test1@example.com', '$2y$10$tFqPVGWoM3j/w1.1dIi1DO0TZVSvvYjMD9ogp0O7TSx98OecnLoS2', 'active', NULL, NULL, NULL, 1, '2025-11-08 17:22:44', 'John Test', 'John Test u.', '42.', 'Budapest', '1234', 'HU'),
(2, 'test2', 'test2@exmaple.com', '$2y$10$.uftHG6vxVpBDwhBbPvokeN5bmVdVzJPIBrLRyRV2ffXpgZ5W.K0O', 'active', NULL, NULL, NULL, 0, '2025-11-09 16:14:59', 'Kiss Tamás Ferenc', 'Petőfi Sándor u.', '63', 'Pálmonostora', '6112', 'HU'),
(3, 'test3', 'test3@exmaple.com', '$2y$10$WeFE/GUSXAviH9j3LQ.sGuck7LMTn5RDHevpl76RVf8vX2unDfK4W', 'active', NULL, NULL, NULL, 0, '2025-11-09 16:50:07', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` tinyint(3) UNSIGNED NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- A nézet helyettes szerkezete `vw_games_with_genres`
-- (Lásd alább az aktuális nézetet)
--
CREATE TABLE `vw_games_with_genres` (
`game_id` bigint(20) unsigned
,`title` varchar(150)
,`price` decimal(10,2)
,`is_published` tinyint(1)
,`genres` mediumtext
);

-- --------------------------------------------------------

--
-- Nézet szerkezete `vw_games_with_genres`
--
DROP TABLE IF EXISTS `vw_games_with_genres`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_games_with_genres`  AS SELECT `g`.`game_id` AS `game_id`, `g`.`title` AS `title`, `g`.`price` AS `price`, `g`.`is_published` AS `is_published`, group_concat(distinct `ge`.`name` order by `ge`.`name` ASC separator ', ') AS `genres` FROM ((`games` `g` left join `game_genres` `gg` on(`gg`.`game_id` = `g`.`game_id`)) left join `genres` `ge` on(`ge`.`genre_id` = `gg`.`genre_id`)) GROUP BY `g`.`game_id`, `g`.`title`, `g`.`price`, `g`.`is_published` ;

--
-- Indexek a kiírt táblákhoz
--

--
-- A tábla indexei `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD UNIQUE KEY `uq_cart_item_unique` (`cart_id`,`game_id`),
  ADD KEY `fk_cart_items_game` (`game_id`);

--
-- A tábla indexei `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `fk_comments_user` (`user_id`),
  ADD KEY `idx_comments_post_created` (`post_id`,`created_at`);

--
-- A tábla indexei `friendships`
--
ALTER TABLE `friendships`
  ADD PRIMARY KEY (`user_id_a`,`user_id_b`),
  ADD KEY `idx_fs_a` (`user_id_a`),
  ADD KEY `idx_fs_b` (`user_id_b`);

--
-- A tábla indexei `friendships_backup`
--
ALTER TABLE `friendships_backup`
  ADD PRIMARY KEY (`user_id`,`friend_user_id`),
  ADD KEY `fk_friendships_friend` (`friend_user_id`);

--
-- A tábla indexei `friend_requests`
--
ALTER TABLE `friend_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `uq_fr_pair` (`requester_id`,`addressee_id`),
  ADD KEY `idx_fr_add_status` (`addressee_id`,`status`,`created_at`),
  ADD KEY `idx_fr_req_status` (`requester_id`,`status`,`created_at`);

--
-- A tábla indexei `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`game_id`);
ALTER TABLE `games` ADD FULLTEXT KEY `ft_games_title_desc` (`title`,`description`);

--
-- A tábla indexei `game_admin_actions`
--
ALTER TABLE `game_admin_actions`
  ADD PRIMARY KEY (`action_id`),
  ADD KEY `fk_gaa_admin` (`admin_user_id`),
  ADD KEY `fk_gaa_game` (`game_id`);

--
-- A tábla indexei `game_genres`
--
ALTER TABLE `game_genres`
  ADD PRIMARY KEY (`game_id`,`genre_id`),
  ADD KEY `fk_game_genres_genre` (`genre_id`);

--
-- A tábla indexei `genres`
--
ALTER TABLE `genres`
  ADD PRIMARY KEY (`genre_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- A tábla indexei `libraries`
--
ALTER TABLE `libraries`
  ADD PRIMARY KEY (`user_id`,`game_id`),
  ADD KEY `fk_libraries_game` (`game_id`);

--
-- A tábla indexei `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`post_id`,`user_id`),
  ADD KEY `fk_likes_user` (`user_id`);

--
-- A tábla indexei `moderation_actions`
--
ALTER TABLE `moderation_actions`
  ADD PRIMARY KEY (`action_id`),
  ADD KEY `fk_moda_admin` (`admin_user_id`),
  ADD KEY `fk_moda_post` (`target_post_id`),
  ADD KEY `fk_moda_comment` (`target_comment_id`),
  ADD KEY `fk_moda_user` (`target_user_id`);

--
-- A tábla indexei `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_orders_user` (`user_id`);

--
-- A tábla indexei `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD UNIQUE KEY `uq_order_item_unique` (`order_id`,`game_id`),
  ADD KEY `fk_order_items_game` (`game_id`);

--
-- A tábla indexei `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_token` (`token_hash`),
  ADD KEY `idx_user` (`user_id`);

--
-- A tábla indexei `play_sessions`
--
ALTER TABLE `play_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `idx_user_open` (`user_id`,`ended_at`),
  ADD KEY `fk_ps_game` (`game_id`);

--
-- A tábla indexei `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `idx_posts_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_posts_game_created` (`game_id`,`created_at`),
  ADD KEY `idx_posts_hidden` (`is_hidden`),
  ADD KEY `fk_posts_moderator` (`moderated_by`);

--
-- A tábla indexei `post_comments`
--
ALTER TABLE `post_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `idx_pc_post` (`post_id`),
  ADD KEY `fk_pc_user` (`user_id`),
  ADD KEY `idx_pc_parent` (`parent_comment_id`);

--
-- A tábla indexei `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`post_id`,`user_id`),
  ADD KEY `fk_pl_user` (`user_id`);

--
-- A tábla indexei `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- A tábla indexei `shopping_carts`
--
ALTER TABLE `shopping_carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `fk_carts_user` (`user_id`);

--
-- A tábla indexei `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_banned_until` (`banned_until`);

--
-- A tábla indexei `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `fk_user_roles_role` (`role_id`);

--
-- A kiírt táblák AUTO_INCREMENT értéke
--

--
-- AUTO_INCREMENT a táblához `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT a táblához `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT a táblához `friend_requests`
--
ALTER TABLE `friend_requests`
  MODIFY `request_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT a táblához `games`
--
ALTER TABLE `games`
  MODIFY `game_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT a táblához `game_admin_actions`
--
ALTER TABLE `game_admin_actions`
  MODIFY `action_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT a táblához `genres`
--
ALTER TABLE `genres`
  MODIFY `genre_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT a táblához `moderation_actions`
--
ALTER TABLE `moderation_actions`
  MODIFY `action_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT a táblához `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT a táblához `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT a táblához `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT a táblához `play_sessions`
--
ALTER TABLE `play_sessions`
  MODIFY `session_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT a táblához `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT a táblához `post_comments`
--
ALTER TABLE `post_comments`
  MODIFY `comment_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT a táblához `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT a táblához `shopping_carts`
--
ALTER TABLE `shopping_carts`
  MODIFY `cart_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT a táblához `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Megkötések a kiírt táblákhoz
--

--
-- Megkötések a táblához `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_items_cart` FOREIGN KEY (`cart_id`) REFERENCES `shopping_carts` (`cart_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_items_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON UPDATE CASCADE;

--
-- Megkötések a táblához `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `fk_comments_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Megkötések a táblához `friendships`
--
ALTER TABLE `friendships`
  ADD CONSTRAINT `fk_fs_a` FOREIGN KEY (`user_id_a`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fs_b` FOREIGN KEY (`user_id_b`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `friendships_backup`
--
ALTER TABLE `friendships_backup`
  ADD CONSTRAINT `fk_friendships_friend` FOREIGN KEY (`friend_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_friendships_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Megkötések a táblához `friend_requests`
--
ALTER TABLE `friend_requests`
  ADD CONSTRAINT `fk_fr_add` FOREIGN KEY (`addressee_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fr_req` FOREIGN KEY (`requester_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `game_admin_actions`
--
ALTER TABLE `game_admin_actions`
  ADD CONSTRAINT `fk_gaa_admin` FOREIGN KEY (`admin_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_gaa_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Megkötések a táblához `game_genres`
--
ALTER TABLE `game_genres`
  ADD CONSTRAINT `fk_game_genres_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_game_genres_genre` FOREIGN KEY (`genre_id`) REFERENCES `genres` (`genre_id`) ON UPDATE CASCADE;

--
-- Megkötések a táblához `libraries`
--
ALTER TABLE `libraries`
  ADD CONSTRAINT `fk_libraries_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_libraries_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Megkötések a táblához `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `fk_likes_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_likes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Megkötések a táblához `moderation_actions`
--
ALTER TABLE `moderation_actions`
  ADD CONSTRAINT `fk_moda_admin` FOREIGN KEY (`admin_user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_moda_comment` FOREIGN KEY (`target_comment_id`) REFERENCES `comments` (`comment_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_moda_post` FOREIGN KEY (`target_post_id`) REFERENCES `posts` (`post_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_moda_user` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Megkötések a táblához `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Megkötések a táblához `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Megkötések a táblához `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Megkötések a táblához `play_sessions`
--
ALTER TABLE `play_sessions`
  ADD CONSTRAINT `fk_ps_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ps_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Megkötések a táblához `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `fk_posts_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`game_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_posts_moderator` FOREIGN KEY (`moderated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_posts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Megkötések a táblához `post_comments`
--
ALTER TABLE `post_comments`
  ADD CONSTRAINT `fk_pc_parent` FOREIGN KEY (`parent_comment_id`) REFERENCES `post_comments` (`comment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pc_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `fk_pl_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Megkötések a táblához `shopping_carts`
--
ALTER TABLE `shopping_carts`
  ADD CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Megkötések a táblához `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

CREATE DATABASE IF NOT EXISTS gamebay CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE gamebay;

CREATE TABLE IF NOT EXISTS games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    price INT NOT NULL,
    image VARCHAR(255) NOT NULL,
    description VARCHAR(255) NOT NULL,
    publisher VARCHAR(100),
    year INT
);

INSERT INTO games (title, price, image, description, publisher, year) VALUES
('Cyberpunk', 11990, 'https://via.placeholder.com/400x200.png?text=Cyberpunk', 'Nyílt világú sci-fi RPG.', 'CD Projekt', 2020),
('Skyrim', 9990, 'images/cyberpunk.jpg', 'Fantasy RPG hatalmas világban.', 'Bethesda', 2011),
('Minecraft', 6990, 'https://via.placeholder.com/400x200.png?text=Minecraft', 'Sandbox kreatív és túlélő játék.', 'Mojang', 2011);

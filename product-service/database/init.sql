CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL,
    image MEDIUMTEXT NOT NULL
);

INSERT INTO products (name, description, price, stock, image) 
VALUES 
('Keyboard Mekanik', 'Keyboard mechanical dengan RGB', 750000, 10, 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADU1EQVR42mNkYPhfDwAChWGA60e6kgAAAABJRU5BrkJggg==');
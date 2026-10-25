const db = require('../config/db');

const ProductModel = {
    getAll: async () => {
        const [rows] = await db.query('SELECT id, name, description, price, stock, image FROM products');
        return rows;
    },

    getById: async (id) => {
        const [rows] = await db.query('SELECT id, name, description, price, stock, image FROM products WHERE id = ?', [id]);
        return rows[0];
    },

    create: async (data) => {
        const { name, description, price, stock, image } = data;
        const [result] = await db.query(
            'INSERT INTO products (name, description, price, stock, image) VALUES (?, ?, ?, ?, ?)',
            [name, description, price, stock, image]
        );
        return result.insertId;
    },

  update: async (id, data) => {
    const { name, description, price, stock, image } = data;
    const [result] = await db.query(
        'UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image = ? WHERE id = ?',
        [name, description, price, stock, image, id]
    );
    return result.affectedRows;
}
};

module.exports = ProductModel;
const ProductModel = require('../models/productModel');

// Fungsi Validasi Base64 Image sesuai ketentuan tugas
function validateBase64Image(imageInput) {
    if (!imageInput || (typeof imageInput === 'string' && imageInput.trim() === '')) {
        return { valid: false, message: 'Field image wajib diisi' };
    }

    let rawBase64 = imageInput;
    if (typeof imageInput === 'string' && imageInput.includes(',')) {
        rawBase64 = imageInput.split(',')[1];
    }

    const base64Regex = /^[A-Za-z0-9+/]+={0,2}$/;
    if (typeof rawBase64 !== 'string' || rawBase64.length % 4 !== 0 || !base64Regex.test(rawBase64)) {
        return { valid: false, message: 'Field image harus berupa Base64 yang valid' };
    }

    const byteSize = Buffer.byteLength(rawBase64, 'base64');
    const maxSizeBytes = 2 * 1024 * 1024; // 2 MB
    if (byteSize > maxSizeBytes) {
        return { valid: false, message: 'Ukuran image maksimal 2 MB' };
    }

    return { valid: true, cleanBase64: rawBase64 };
}

exports.getAllProducts = async (req, res) => {
    try {
        const products = await ProductModel.getAll();
        return res.status(200).json(products);
    } catch (error) {
        return res.status(500).json({ message: 'Internal Server Error', error: error.message });
    }
};

exports.getProductById = async (req, res) => {
    try {
        const product = await ProductModel.getById(req.params.id);
        if (!product) {
            return res.status(404).json({ message: 'Produk tidak ditemukan' });
        }
        return res.status(200).json(product);
    } catch (error) {
        return res.status(500).json({ message: 'Internal Server Error', error: error.message });
    }
};

exports.createProduct = async (req, res) => {
    const { name, description, price, stock, image } = req.body;

    const imageValidation = validateBase64Image(image);
    if (!imageValidation.valid) {
        return res.status(400).json({ message: imageValidation.message });
    }

    try {
        const insertId = await ProductModel.create({
            name,
            description,
            price,
            stock,
            image: imageValidation.cleanBase64
        });

        return res.status(201).json({
            message: 'Data produk tersimpan',
            id: insertId
        });
    } catch (error) {
        return res.status(500).json({ message: 'Internal Server Error', error: error.message });
    }
};

exports.updateProduct = async (req, res) => {
    const { id } = req.params;
    const { name, description, price, stock, image } = req.body;

    const imageValidation = validateBase64Image(image);
    if (!imageValidation.valid) {
        return res.status(400).json({ message: imageValidation.message });
    }

    try {
        const affectedRows = await ProductModel.update(id, {
            name,
            description,
            price,
            stock,
            image: imageValidation.cleanBase64
        });

        if (affectedRows === 0) {
            return res.status(404).json({ message: 'Produk tidak ditemukan' });
        }

        return res.status(200).json({ message: 'Data produk diperbarui' });
    } catch (error) {
        return res.status(500).json({ message: 'Internal Server Error', error: error.message });
    }
}


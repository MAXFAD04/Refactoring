import { query } from "./database.js";

export class ManagementModel {
    static async create(newManagementData) {
        const {product, quantity} = newManagementData;
        const result = await query("INSERT INTO management (product, quantity) VALUES ($1, $2) RETURNING *", [product, quantity]);
        return result.rows[0];
    }

    static async update(id, updatedManagementData) {
        const {product, quantity} = updatedManagementData;
        const result = await query("UPDATE management SET product = $1, quantity = $2 WHERE id = $3 RETURNING *", [product, quantity, id]);
        return result.rows[0];
    }

    static async decreaseQuantity(updatedQuantity) {
        const {product, quantity} = updatedQuantity;
        const result = await query("UPDATE management SET quantity = $1 WHERE product = $2 RETURNING *", [quantity, product]);
        return result.rows[0];
    }

    static async getAll() {
        const result = await query("SELECT * FROM management");
        if (result.rows.length == 0) {
            await query("TRUNCATE TABLE management RESTART IDENTITY");
        }
        return result.rows;
    }

    static async getById(id) {
        const result = await query("SELECT product, quantity FROM management WHERE id = $1", [id]);
        return result.rows[0];
    }

    static async getByProduct(product) {
        const result = await query("SELECT product, quantity FROM management WHERE product = $1", [product]);
        return result.rows[0];
    }

    static async delete(id) {
        const result = await query("DELETE FROM management WHERE id = $1 RETURNING *", [id]);
        return result.rows[0];
    }
}
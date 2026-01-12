import { query } from "./database.js";

export class OrderModel {
    static async create(orderData) {
        const {user_id, product} = orderData;
        const result = await query('INSERT INTO orders (user_id, product) VALUES ($1, $2) RETURNING *', [user_id, product]);
        return result.rows[0];
    }

    static async getAll() {
        const result = await query('SELECT * FROM orders');
        if (result.rows.length == 0) {
            await query("TRUNCATE TABLE orders RESTART IDENTITY");
        }
        return result.rows;
    }

    static async getById(order_id) {
        const result = await query('SELECT user_id, product FROM orders WHERE id = $1', [order_id]);
        return result.rows[0];
    }

    static async update(order_id, newOrderData) {
        const {user_id, product} = newOrderData;
        const result = await query('UPDATE orders SET product = $1 WHERE id = $2 RETURNING *', [product, order_id]);
        return result.rows[0];
    }

    static async delete(order_id) {
        const result = await query('DELETE FROM orders WHERE id = $1 RETURNING *', [order_id]);
        return result.rows[0];
    }
};
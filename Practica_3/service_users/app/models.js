import { query } from './database.js';

export class UserModel {
  static async create(userData) {
    const { email, name } = userData;
    const result = await query(
      `INSERT INTO users (email, name) 
       VALUES ($1, $2) RETURNING *`,
      [email, name]
    );
    return result.rows[0];
  }

  static async findById(id) {
    const result = await query('SELECT email, name FROM users WHERE id = $1', [id]);
    return result.rows[0];
  }

  static async update(id, updateData) {
    const { email, name } = updateData;
    const result = await query(
      'UPDATE users SET email = $1, name = $2 WHERE id = $3 RETURNING *',
      [email, name, id]
    );
    return result.rows[0];
  }

  static async getAll() {
    const result = await query('SELECT * FROM users');
    if (result.rows.length == 0) {
      await query("TRUNCATE TABLE users RESTART IDENTITY")
    }
    return result.rows;
  }

  static async delete(id) {
    const result = await query('DELETE FROM users WHERE id = $1 RETURNING *', [id]);
    return result.rows[0];
  }
}
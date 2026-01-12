import { Pool } from 'pg';

const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
});

export const query = (text, params) => pool.query(text, params);

export const getClient = () => pool.connect();

export default pool;
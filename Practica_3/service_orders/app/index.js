import express from 'express';
import cors from 'cors';
import ordersRouter from './routes/orders.js';

const app = express();
const PORT = process.env.PORT || 8002;

app.use(cors());
app.use(express.json());

app.use('/orders', ordersRouter);

app.get('/health', (req, res) => {
  res.json({ status: 'OK', service: 'orders' });
});

app.use((err, req, res, next) => {
  console.error(err.stack);
  res.status(500).json({ error: 'Something went wrong!' });
});

app.listen(PORT, () => {
  console.log(`Orders service running on port ${PORT}`);
});
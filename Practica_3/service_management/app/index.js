import express from 'express';
import cors from 'cors';
import ManagementRouter from './routes/management.js';

const app = express();
const PORT = process.env.PORT || 8003;

app.use(cors());
app.use(express.json());

app.use('/management', ManagementRouter);

app.get('/health', (req, res) => {
  res.json({ status: 'OK', service: 'management' });
});

app.use((err, req, res, next) => {
  console.error(err.stack);
  res.status(500).json({ error: 'Something went wrong!' });
});

app.listen(PORT, () => {
  console.log(`Management service running on port ${PORT}`);
});
import redis from 'redis';

const client = redis.createClient({
    url: process.env.REDIS_URL,
    host: "cache"
});

client.on('error', (err) => console.log('Redis client error', err));
client.on('connect', () => console.log('Redis client connected'));
client.on('ready', () => console.log('Redis ready'));

client.connect();

export default client;
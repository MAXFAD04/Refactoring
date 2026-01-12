import { UserModel } from '../models.js';
import redisClient from '../redis_client.js';

export class UserService {
  static async createUser(userData) {
    const user = await UserModel.create(userData);
    
    await redisClient.del('users:all');
    
    return user;
  }

  static async getAllUsers() {
    const cachedUsers = await redisClient.get('user:all');

    if (cachedUsers) {
      return JSON.parse(cachedUsers);
    }

    const users = await UserModel.getAll();

    if (users && users.length != 0) {
      await redisClient.setEx('user:all', 300, JSON.stringify(users));
    }

    return users;
  }

  static async getUserById(id) {
    const cacheKey = `user:${id}`;
    
    const cachedUser = await redisClient.get(cacheKey);
    if (cachedUser) {
      return JSON.parse(cachedUser);
    }

    const user = await UserModel.findById(id);
    
    if (user && user.length != 0) {
      await redisClient.setEx(cacheKey, 300, JSON.stringify(user));
    }
    
    return user;
  }

  static async updateUser(id, userData) {
    const user = await UserModel.update(id, userData);
    
    if (user) {
      await redisClient.del(`user:${id}`);
      await redisClient.del('users:all:*');
    }
    
    return user;
  }

  static async deleteUser(id) {
    const user = await UserModel.delete(id);
    
    if (user && user.length != 0) {
      await redisClient.del(`user:${id}`);
      await redisClient.del('users:all:*');
    }
    
    return user;
  }
}
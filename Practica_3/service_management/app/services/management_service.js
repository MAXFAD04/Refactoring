import { ManagementModel } from '../models.js';
import redisClient from '../redis_client.js';

export class ManagementService {
    static async createManagement(newManagementData) {
        const newManagement = await ManagementModel.create(newManagementData);

        return newManagement;
    }

    static async getAllManagement() {
        const cachedManagements = await redisClient.get('management:all');

        if (cachedManagements) {
            return JSON.parse(cachedManagements);
        }

        const managements = await ManagementModel.getAll();

        if (managements && managements.length != 0) {
            await redisClient.setEx('management:all', 300, JSON.stringify(managements));
        }

        return managements;
    }

    static async getManagementById(id) {
        const cachedManagement = await redisClient.get(`management:${id}`);

        if (cachedManagement) {
            return JSON.parse(cachedManagement);
        }

        const management = await ManagementModel.getById(id);

        if (management && management.length != 0) {
            await redisClient.setEx(`management:${id}`, 300, JSON.stringify(management));
        }

        return management;
    }

    static async decreaseQuantity(updatedQuantity) {
        const {product, quantity} = updatedQuantity;
        const decreasedQuantity = await ManagementModel.decreaseQuantity(updatedQuantity);

        if (decreasedQuantity) {
            await redisClient.del(`management:${product}`);
            await redisClient.del('management:all');
            await redisClient.del(`management:${decreasedQuantity.id}`);
        }

        return decreasedQuantity;
    }

    static async getManagementByProduct(product) {
        const cachedManagement = await redisClient.get(`management:${product}`);

        if (cachedManagement) {
            return JSON.parse(cachedManagement);
        }

        const management = await ManagementModel.getByProduct(product);

        if (management && management.length != 0) {
            await redisClient.setEx(`management:${product}`, 300, JSON.stringify(management));
        }

        return management;
    }

    static async updateManagement(id, updatedManagementData) {
        const updatedManagement = await ManagementModel.update(id, updatedManagementData);

        if (updatedManagement) {
            await redisClient.del(`management:${id}`);
            await redisClient.del('management:all');
        }

        return updatedManagement;
    }

    static async deleteManagement(id) {
        const deletedManagement = await ManagementModel.delete(id);

        if (deletedManagement) {
            await redisClient.del(`management:${id}`);
        }

        return deletedManagement;
    }
}

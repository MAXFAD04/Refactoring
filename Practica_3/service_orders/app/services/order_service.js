import { OrderModel } from "../models.js";
import redisClient from '../redis_client.js';

export class OrderService {
    static async createOrder(newOrderData) {
        const newOrder = await OrderModel.create(newOrderData);

        await redisClient.del('order:all');

        return newOrder;
    }

    static async getOrderAll() {
        const cachedOrders = await redisClient.get(`order:all`);

        if (cachedOrders) {
            return JSON.parse(cachedOrders);
        }

        const orders = await OrderModel.getAll();

        if (orders && orders.length != 0) {
            await redisClient.setEx(`order:all`, 300, JSON.stringify(orders)); 
        }

        return orders;

    }

    static async getOrderById(order_id) {
        const cachedOrder = await redisClient.get(`order:${order_id}`);
        
        if (cachedOrder) {
            return JSON.parse(cachedOrder);
        }

        const order = await OrderModel.getById(order_id);
    
        if (order && order.length != 0) {
            await redisClient.setEx(`order:${order_id}`, 300, JSON.stringify(order));
        }
        
        return order;
    }

    static async updateOrder(id, orderData) {
        const updatedOrder = await OrderModel.update(id, orderData);

        if (updatedOrder) {
            await redisClient.del(`order:${order_id}`);
            await redisClient.del('order:all');
        }

        return updatedOrder;
    }

    static async deleteOrder(id) {
        const deletedOrder = await OrderModel.delete(id);

        if (deletedOrder && deletedOrder.length != 0) {
            await redisClient.del(`order:${order_id}`);
            await redisClient.del('order:all');
        }
        return deletedOrder;
    }
};
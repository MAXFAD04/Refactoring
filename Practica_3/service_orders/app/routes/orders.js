import express from 'express';
import { OrderService } from '../services/order_service.js';
import { createSchema, updateSchema } from '../schemas.js';

const router = express.Router();

router.get('/', async (req, res) => {
    try {
        const orders = await OrderService.getOrderAll();

        if (!orders || orders.length == 0) {
            return res.status(404).json({error: "Orders not found"});
        }

        res.json(orders);
    } catch (error) {
        return res.status(500).json({error: error.message});
    }
});

router.get('/:id', async (req, res) => {
    try {
        const order = await OrderService.getOrderById(req.params.id);

        if (!order || order.length == 0) {
            return res.status(404).json({error: "Order not found"});
        }

        res.json(order);
    } catch (error) {
        return res.status(500).json({error: error.message});
    }
});

router.post('/', async (req, res) => {
    try {
        const {error, value} = createSchema.validate(req.body);

        if (error) {
            return res.status(400).json({ error: error.details[0].message });
        }

        const newOrder = await OrderService.createOrder(value);

        return res.status(201).json(newOrder);
    } catch (error) {
        return res.status(500).json({error: error.message});
    }
});

router.put('/:id', async (req, res) => {
    try {
        const {error, value} = updateSchema.validate(req.body);

        if (error) {
            return res.status(400).json({ error: error.details[0].message });
        }

        const updatedOrder = await OrderService.updateOrder(req.params.id, value);

        if (!updatedOrder) {
            return res.status(404).json({error: "Order not found"});
        }

        return res.json(updatedOrder);

    } catch (error) {
        return res.status(500).json({error: error.message});
    }
});

router.delete('/:id', async (req, res) => {
    try {
        const deletedOrder = await OrderService.deleteOrder(req.params.id);
        
        if (!deletedOrder || deletedOrder.length === 0) {
            return res.status(404).json({error: "Order not found"});
        }

        return res.status(200).json(deletedOrder);

    } catch (error) {
        return res.status(500).json({error: error.message});
    }
});

export default router;
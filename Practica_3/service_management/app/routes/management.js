import express from 'express';
import { createSchema, updateSchema, productSchema } from '../schemas.js';
import { ManagementService } from '../services/management_service.js';

const router = express.Router();

router.get(`/product`, async (req, res) => {
    try {
        const management = await ManagementService.getManagementByProduct(req.query.product);

        if (!management || management.length == 0) {
            return res.json({"quantity": -1});
        }

        return res.json(management);
    } catch (error) {
        return res.status(500).json({error: error.message});
    }
});

router.put('/product', async (req, res) => {
    try {
        const {error, value} = updateSchema.validate(req.body);

        if (error) {
            return res.status(400).json({error: error.details[0].message});
        }

        const management = await ManagementService.decreaseQuantity(value);

        if (!management) {
            return res.status(404).json({error: "Management not found"});
        }

        return res.json(management);

    } catch (error) {
        return res.status(500).json({error: error.message});
    }
});

router.get('/:id', async (req, res) => {
    try {
        const management = await ManagementService.getManagementById(req.params.id);
        
        if (!management) {
            return res.status(404).json({error: "Management not found"});
        }

        return res.json(management);
    } catch (error) {
        return res.status(500).json({error: error.message});
    }
});

router.get('/', async (req, res) => {
    try {
        const managements = await ManagementService.getAllManagement();

        if (!managements || managements.length == 0) {
            return res.status(404).json({error: "Managements not found"});
        }

        return res.json(managements);
    } catch (error) {
        return res.status(500).json({error: error.message});
    }
});

router.post('/', async (req, res) => {
    try {
        const {error, value} = createSchema.validate(req.body);

        if (error) {
            return res.status(400).json({error: error.details[0].message});
        }

        const management = await ManagementService.createManagement(value);

        return res.status(201).json(management);
    } catch (error) {
        return res.status(500).json({error: error.message});
    }
});

router.put('/:id', async (req, res) => {
    try {
        const {error, value} = updateSchema.validate(req.body);

        if (error) {
            return res.status(400).json({error: error.details[0].message});
        }

        const management = await ManagementService.updateManagement(req.params.id, value);

        if (!management) {
            return res.status(404).json({error: "Management not found"});
        }

        return res.json(management);

    } catch (error) {
        return res.status(500).json({error: error.message});
    }
});



router.delete('/:id', async (req, res) => {
    try {

    } catch (error) {
        return res.status(500).json({error: error.message});
    }
});

export default router;
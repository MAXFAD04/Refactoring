import Joi from 'joi';

export const createSchema = Joi.object({
    product: Joi.string().min(3).max(30).required(),
    quantity: Joi.number().integer().min(0).required()
});

export const updateSchema = Joi.object({
    product: Joi.string().min(3).max(30),
    quantity: Joi.number().integer().min(0)
});

export const productSchema = Joi.object({
    product: Joi.string().min(3).max(30)
});

import Joi from 'joi';

export const createSchema = Joi.object({
    user_id: Joi.number().integer().positive().required(),
    product: Joi.string().min(3).max(30).required()
});

export const updateSchema = Joi.object({
    user_id: Joi.number().integer(),
    product: Joi.string().min(3).max(30)
});
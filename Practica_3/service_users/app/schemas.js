import Joi from 'joi';

export const createSchema = Joi.object({
  name: Joi.string().min(3).max(30).required(),
  email: Joi.string().email().required()
});

export const updateSchema = Joi.object({
  name: Joi.string().min(3).max(30),
  email: Joi.string().email()
});
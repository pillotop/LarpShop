import { Router } from "express";
import { prisma } from "../db.js";

export const userRouter = Router();

userRouter.get("/", async (_req, res) => {
  const users = await prisma.user.findMany();

  res.json(users);
});

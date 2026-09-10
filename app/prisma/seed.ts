import "dotenv/config";
import { PrismaBetterSqlite3 } from "@prisma/adapter-better-sqlite3";
import { PrismaClient } from "../src/generated/prisma/client.js";

const adapter = new PrismaBetterSqlite3({
  url: process.env.DATABASE_URL!,
})

const prisma = new PrismaClient({ adapter });

async function main() {
  await prisma.user.upsert({
    where: {
      username: "alice",
    },
    update: {},
    create: {
      username: "alice",
      email: "alice@example.test",
    },
  });

  await prisma.user.upsert({
    where: {
      username: "bob",
    },
    update: {},
    create: {
      username: "bob",
      email: "bob@example.test",
    },
  });

  console.log("database seeded");
}

main()
  .catch((error) => {
    console.log(error);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  })

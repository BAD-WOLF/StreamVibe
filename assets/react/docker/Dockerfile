# ---- Stage 1: Build ----
FROM node:20-slim AS base

WORKDIR /app

# Copy only dependency files first to leverage Docker cache
COPY package.json yarn.lock ./

# Set Yarn cache folder and install dependencies
ENV YARN_CACHE_FOLDER=/usr/local/share/.yarn-cache
RUN mkdir -p $YARN_CACHE_FOLDER && yarn install --immutable

# apt install
RUN apt update -y && apt install curl git fish neovim -y

# Copy the rest of the project and build
COPY . .

# ---- build stage (production build) ----
FROM base AS build
# run build (produces /app/dist)
RUN yarn build

# ---- prod stage (lightweight runtime) ----
FROM node:20-slim AS prod
WORKDIR /app

# copy only dist from build stage (no source, minimal)
COPY --from=build /app/dist /app/dist

# Use a lightweight static server; npx serve avoids global install
CMD ["npx", "serve", "-s", "dist", "-l", "5173"]

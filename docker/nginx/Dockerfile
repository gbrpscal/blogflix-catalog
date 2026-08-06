FROM node:22.19.0-bookworm-slim AS frontend-dependencies
WORKDIR /workspace
COPY apps/frontend/package.json apps/frontend/package-lock.json ./
RUN npm ci --ignore-scripts

FROM frontend-dependencies AS frontend-tooling
COPY apps/frontend .
CMD ["npm", "test"]

FROM frontend-dependencies AS frontend-build
COPY apps/frontend .
RUN npm run build

FROM nginx:1.28.0-alpine3.21 AS runtime
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=frontend-build /workspace/dist /usr/share/nginx/html
EXPOSE 8080

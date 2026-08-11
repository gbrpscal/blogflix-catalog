FROM node:22.19.0-bookworm-slim AS frontend-dependencies

WORKDIR /workspace

COPY apps/frontend/package.json apps/frontend/package-lock.json ./
RUN npm ci --ignore-scripts

FROM frontend-dependencies AS frontend-build

COPY apps/frontend .
RUN npm run build

FROM nginx:1.28.0-alpine3.21 AS runtime

ENV PORT=8080 \
    BACKEND_HOST=backend.railway.internal \
    BACKEND_PORT=9000 \
    NGINX_ENVSUBST_FILTER="^(PORT|BACKEND_HOST|BACKEND_PORT|DNS_RESOLVER)$"

COPY docker/railway/default.conf.template /etc/nginx/templates/default.conf.template
COPY --chmod=0755 docker/railway/15-resolver.envsh /docker-entrypoint.d/15-resolver.envsh
COPY --from=frontend-build /workspace/dist /usr/share/nginx/html

EXPOSE 8080

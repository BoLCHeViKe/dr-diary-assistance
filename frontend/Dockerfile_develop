FROM node:22-alpine

RUN npm install -g @angular/cli

WORKDIR /app

EXPOSE 4200

CMD ["sh", "-c", "npm install && ng serve --host 0.0.0.0 --port 4200"]

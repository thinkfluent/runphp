# Serverless PHP Toolkit for Google Cloud Run

The `thinkfluent/runphp` toolkit enables rapid application development and serverless hosting on [Google Cloud Run](https://cloud.google.com/run).

## Getting Started

### The Vanilla Build

This should start a local instance of the default runphp image.
```bash
docker run --rm -e "RUNPHP_MODE=development" -e "PORT=80" -p 8080:80 fluentthinking/runphp:latest
```

You should be able to access the default home page and admin interfaces as follows:
* http://localhost:8080
* http://localhost:8080/_runphp

### Building Your Application

You will most likely need a `Dockerfile`, here is a simple example, which uses the baked in apache configs.

```Dockerfile
FROM fluentthinking/runphp:latest

RUN mkdir /app
COPY . /app

ENV RUNPHP_COMPOSER_PATH="/app"
ENV RUNPHP_DOC_ROOT="/app/public"
ENV RUNPHP_INDEX_FILE="index.php"
```

```bash
docker build -t myapp .
```
```bash
docker run --rm -e "RUNPHP_MODE=development" -e "PORT=80" -p 8080:80 myapp:latest
```

#### Pushing to Cloud Run

These examples assume the "latest" tag, but in reality, you should use a semver or equivalent tag.

```bash
docker tag myapp:latest eu.gcr.io/<google-project>/myapp:latest
```
```bash
docker push eu.gcr.io/<google-project>/myapp:latest
```
```bash
gcloud run deploy <cloud-run-service-name> \
    --image=eu.gcr.io/<google-project>/myapp:latest \
    --platform managed \
    --allow-unauthenticated \
    --set-env-vars "RUNPHP_MODE=development" \
    --region europe-west1 \
    --project <google-project>
```

## Customisation

If you want to roll your own apache configs, you can disable the runphp sites in your Dockerfile with
```
RUN a2dissite 002-runphp
``` 

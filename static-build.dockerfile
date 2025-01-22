FROM --platform=linux/amd64 dunglas/frankenphp:static-builder

# Copy your app
WORKDIR /go/src/app/dist/app
COPY . .

# Remove the tests and other unneeded files to save space
# Alternatively, add these files to a .dockerignore file
RUN rm -Rf tests/
RUN rm -Rf .git
RUN rm -Rf .editorconfig
RUN rm -Rf .gitattributes
RUN rm -Rf .github
RUN rm -Rf .gitignore
RUN rm -Rf CODE-OF-CONDUCT.md
RUN rm -Rf CONTRIBUTING.md
RUN rm -Rf LICENSE.md
RUN rm -Rf Makefile
RUN rm -Rf docker/
RUN rm -Rf ansible/
RUN rm -Rf docker-compose.prod.yml
RUN rm -Rf docker-compose.dev.yml
RUN rm -Rf README.md
RUN rm -Rf supervisord.conf
RUN rm -Rf supervisord.log
RUN rm -Rf phpunit.xml
RUN rm -Rf static-build.dockerfile
RUN rm -Rf codecov.yml
RUN rm -Rf app-prod.dockerfile
RUN rm -Rf buzzer-deploy-test.jenkinsfile
RUN rm -Rf buzzer-deploy-prod.jenkinsfile

# Build the static binary
WORKDIR /go/src/app/
RUN EMBED=dist/app/ ./build-static.sh

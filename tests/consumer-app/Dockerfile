FROM prooph/composer:7.3

ARG MAGENTO_PUBLIC_KEY
ARG MAGENTO_PRIVATE_KEY

# docker build --tag swedbankpay/magento2-checkout:latest --build-arg MAGENTO_PUBLIC_KEY=<MAGENTO_PUBLIC_KEY> --build-arg MAGENTO_PRIVATE_KEY=<MAGENTO_PRIVATE_KEY> . --file tests/consumer-app/Dockerfile
# docker run --rm --volume $(pwd)/tests/consumer-app:/app --volume $(pwd):/plugin --env VERSION=1.2.3 swedbankpay/magento2-checkout:latest

WORKDIR /consumer-app
COPY ./tests/consumer-app /consumer-app

RUN sed -i -e "s/MAGENTO_PUBLIC_KEY/${MAGENTO_PUBLIC_KEY}/g" auth.json
RUN sed -i -e "s/MAGENTO_PRIVATE_KEY/${MAGENTO_PRIVATE_KEY}/g" auth.json

VOLUME /plugin

ENTRYPOINT composer install

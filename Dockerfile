FROM php:8.2-cli

WORKDIR /app

# Copy application files from core-store-engine
COPY core-store-engine/ /app/

# Ensure required directories and permissions
RUN mkdir -p /app/data /app/uploads /app/public/uploads \
    && chmod -R 777 /app/data /app/uploads /app/public/uploads \
    && (test -e /app/public/admin || ln -s /app/admin /app/public/admin)

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]

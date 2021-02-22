DOCKERFILES = Dockerfile-8.0
SRCS := $(shell find docker nginx-lua -type f)
.PHONY: all
all: images

include ../project.Makefile
RUNTIME := php-runtime
REGISTRY := $(REGISTRY)/$(RUNTIME)
include ../docker.Makefile

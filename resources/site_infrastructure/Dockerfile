FROM ubuntu:20.04

RUN \
  apt-get update && \
  apt-get -y upgrade && \
  apt-get install -y curl && \
  apt-get install -y vim && \
  apt-get install -y git && \
  apt-get install -y unzip && \
  DEBIAN_FRONTEND=noninteractive apt-get install -y software-properties-common && \
  apt-get install -y gnupg2 && \
  apt-get install -y rpl && \
  # Terraform
  curl -fsSL https://apt.releases.hashicorp.com/gpg | apt-key add - && \
  apt-add-repository "deb [arch=amd64] https://apt.releases.hashicorp.com $(lsb_release -cs) main" && \
  apt-get update && apt-get install terraform  && \
  # AWS
  curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip" && unzip awscliv2.zip && aws/install

ENV AWS_ACCESS_KEY_ID=placeholder
ENV AWS_SECRET_ACCESS_KEY=placeholder
ENV AWS_REGION=us-west-2
ENV SITE_NAME=placeholder

WORKDIR /home

RUN apt-get install -y rpl

COPY main.tf /home/main.tf

CMD rpl -w -f "_SITE_NAME_" $SITE_NAME main.tf && rpl -w -f "_REGION_NAME_" $AWS_REGION main.tf && terraform init && terraform apply -auto-approve

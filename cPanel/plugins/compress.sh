#!/bin/sh

currentdir=$(dirname $0)
cd $currentdir

echo "Compressing plugins..."

rm -f paperlantern.tar.gz
rm -f x3.tar.gz

tar -zcvf paperlantern.tar.gz paperlantern/*
tar -zcvf x3.tar.gz x3/*

